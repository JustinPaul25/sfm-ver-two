import os
import time
import cv2
import numpy as np
import requests
from datetime import datetime

# Kivy and KivyMD imports
from kivy.core.window import Window
from kivy.graphics.texture import Texture
from kivy.clock import Clock
from kivy.uix.image import Image
from kivy.uix.label import Label
from kivymd.uix.anchorlayout import MDAnchorLayout
from kivymd.uix.card import MDCard
from kivymd.app import MDApp
from kivymd.uix.boxlayout import MDBoxLayout
from kivymd.uix.button import MDRaisedButton
from kivymd.uix.textfield import MDTextField
from kivymd.uix.label import MDIcon
from kivymd.uix.snackbar import Snackbar

# YOLOv8
from ultralytics import YOLO

# --- Configuration ---
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
OUTPUT_DIR = os.path.join(BASE_DIR, "output_frames")
os.makedirs(OUTPUT_DIR, exist_ok=True)

MODEL_PATH = os.path.join(BASE_DIR, "best_SMF.pt")

# Calibration constants
REAL_LENGTH_CM = 2.54
REAL_WIDTH_CM = 30.0
IMAGE_WIDTH_PX = 1200
IMAGE_LENGTH_PX = 127.13

# Stage thresholds (in inches)
STARTER_MAX_IN = 3.0
GROWER_MAX_IN = 6.0

# API key from environment variable
# Set this in your .env file or environment: API_KEY=your-secret-key-here
API_KEY = os.getenv("API_KEY", "default-api-key")

Window.size = (1200, 760)
Window.clearcolor = (0, 0, 0, 1)


class KivyCamera(Image):
    def __init__(self, info_label, saved_frame_widget, saved_info_label, saved_icon, app_ref, **kwargs):
        super().__init__(**kwargs)
        self.capture = None
        self.info_label = info_label
        self.saved_frame_widget = saved_frame_widget
        self.saved_info_label = saved_info_label
        self.saved_icon = saved_icon
        self.app_ref = app_ref

        print(f"⏳ Loading YOLO model: {MODEL_PATH}")
        self.model = YOLO(MODEL_PATH)
        print("✅ Model loaded successfully")

        self.colors = {"Starter": (0, 255, 0), "Grower": (255, 255, 0), "Finisher": (255, 0, 0)}
        self.stage_icons = {"Starter": "sprout", "Grower": "fish", "Finisher": "flag-checkered"}

        self.real_length_cm = REAL_LENGTH_CM
        self.real_width_cm = REAL_WIDTH_CM
        self.image_width_pixels = IMAGE_WIDTH_PX
        self.image_length_pixels = IMAGE_LENGTH_PX

        self.last_save_time = 0.0
        self.save_interval = 3.0
        self._event = None

        self.latest_width = None
        self.latest_length = None
        self.latest_weight = None  # <-- Store latest weight here

        self.current_sampling_id = None

    def start(self, fps=20):
        if self.capture is None:
            self.capture = cv2.VideoCapture(0, cv2.CAP_DSHOW)
            self.capture.set(cv2.CAP_PROP_FRAME_WIDTH, 1280)
            self.capture.set(cv2.CAP_PROP_FRAME_HEIGHT, 720)
            time.sleep(0.2)

        if self._event is None:
            self._event = Clock.schedule_interval(self.update, 1.0 / fps)

        print("🎬 Camera started")

    def stop(self):
        if self._event is not None:
            self._event.cancel()
            self._event = None
        if self.capture is not None:
            if self.capture.isOpened():
                self.capture.release()
            self.capture = None
        print("⏸ Camera stopped")

    def update(self, dt):
        if self.capture is None:
            frame = np.zeros((720, 1280, 3), dtype=np.uint8)
            cv2.putText(frame, "🎥 No camera detected", (200, 360), cv2.FONT_HERSHEY_SIMPLEX, 1.0, (200, 200, 200), 2)
        else:
            ret, frame = self.capture.read()
            if not ret or frame is None:
                frame = np.zeros((720, 1280, 3), dtype=np.uint8)
                cv2.putText(frame, "Waiting for camera...", (200, 360), cv2.FONT_HERSHEY_SIMPLEX, 1.0, (200, 200, 200), 2)
            else:
                try:
                    results = self.model(frame, verbose=False)
                    detections = results[0].boxes
                except Exception as e:
                    print(f"Model inference error: {e}")
                    detections = []

                detected_info = []
                for box in detections:
                    try:
                        conf = float(box.conf)
                        xyxy = box.xyxy[0].cpu().numpy().astype(int)
                    except Exception:
                        continue

                    if conf < 0.9:
                        continue

                    pixel_width = xyxy[2] - xyxy[0]
                    pixel_length = xyxy[3] - xyxy[1]

                    real_world_length_cm = (pixel_length / self.image_length_pixels) * self.real_length_cm
                    real_world_width_cm = (pixel_width / self.image_width_pixels) * self.real_width_cm

                    real_world_length_in = real_world_length_cm / 2.54
                    real_world_width_in = real_world_width_cm / 2.54

                    formatted_length = f"{real_world_length_in:.2f}"
                    formatted_width = f"{real_world_width_in:.2f}"

                    if real_world_width_in <= STARTER_MAX_IN:
                        stage = "Starter"
                    elif real_world_width_in <= GROWER_MAX_IN:
                        stage = "Grower"
                    else:
                        stage = "Finisher"

                    color = self.colors.get(stage, (0, 255, 0))
                    cv2.rectangle(frame, (xyxy[0], xyxy[1]), (xyxy[2], xyxy[3]), color, 2)
                    cv2.putText(frame, "Tilapia", (xyxy[0], xyxy[1] - 8), cv2.FONT_HERSHEY_SIMPLEX, 0.7, color, 2)

                    detected_info.append((stage, conf, formatted_width, formatted_length))

                    # Save frame and update info panel every save_interval seconds
                    if time.time() - self.last_save_time > self.save_interval:
                        output_path = os.path.join(OUTPUT_DIR, "frame.png")
                        cv2.imwrite(output_path, frame)
                        self.last_save_time = time.time()

                        if self.saved_frame_widget:
                            self.saved_frame_widget.source = output_path
                            self.saved_frame_widget.reload()

                        if self.saved_info_label:
                            now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                            weight_display = self.latest_weight if self.latest_weight is not None else "---"
                            self.saved_info_label.text = (
                                f"[size=18][b]Stage:[/b] [color=00ffcc]{stage}[/color]\n"
                                f"[b]Width:[/b] [color=00ffcc]{formatted_width}[/color] in\n"
                                f"[b]Length:[/b] [color=00ffcc]{formatted_length}[/color] in\n"
                                f"[b]Weight:[/b] [color=00ffcc]{weight_display}[/color] g\n"
                                f"[b]Time:[/b] [color=cccccc]{now}[/color][/size]"
                            )
                            self.saved_icon.icon = self.stage_icons.get(stage, "fish")

                            self.latest_width = formatted_width
                            self.latest_length = formatted_length

                if detected_info:
                    summaries = [t[0] for t in detected_info]
                    self.info_label.text = "Detected: " + ", ".join(summaries)
                else:
                    self.info_label.text = "No Tilapia detected."

        if 'frame' in locals():
            buf = cv2.flip(frame, 0).tobytes()
            texture = Texture.create(size=(frame.shape[1], frame.shape[0]), colorfmt='bgr')
            texture.blit_buffer(buf, colorfmt='bgr', bufferfmt='ubyte')
            self.texture = texture

    def fetch_weight_from_api(self, doc=None):
        if not self.latest_width or not self.latest_length:
            print("⚠️ No detection data available.")
            return

        if not doc:
            print("❌ DOC not provided.")
            return

        print(f"📡 Looking up sampling using DOC: {doc}")

        try:
            # Step 1: Get all cages with samplings
            url_cages = f"https://sfm-ver-two.on-forge.com/api/cages?key={API_KEY}"
            cages_response = requests.get(url_cages)
            cages_json = cages_response.json()

            if cages_response.status_code != 200 or "data" not in cages_json:
                print("❌ Failed to fetch cages data.")
                return

            # Step 2: Search for sampling with matching DOC and debug print all samplings found
            sampling_id = None
            print("🔍 Available samplings:")
            for cage in cages_json["data"]:
                samplings = cage.get("samplings", [])
                for sampling in samplings:
                    print(f"  Sampling ID: {sampling.get('id')}, DOC: {sampling.get('doc')}")
                    if sampling.get("doc") == doc:
                        sampling_id = sampling.get("id")
                        print(f"✅ Matched DOC found with sampling ID: {sampling_id}")
                        break
                if sampling_id is not None:
                    break

            if sampling_id is None:
                print(f"❌ Sampling with DOC {doc} not found.")
                return

            # Step 3: Calculate weight with the found sampling_id and doc included
            url_weight = f"https://sfm-ver-two.on-forge.com/api/weight?key={API_KEY}"
            payload = {
                "sampling_id": sampling_id,
                "doc": doc,  # Required by API
                "width": float(self.latest_width),
                "height": float(self.latest_length)
            }

            print(f"📡 Sending weight calculation: {payload}")

            weight_response = requests.post(url_weight, json=payload)
            weight_json = weight_response.json()

            print(f"📩 Weight status: {weight_response.status_code}")
            print(f"📩 Weight response: {weight_json}")

            if weight_response.status_code != 200 or "data" not in weight_json:
                print(f"❌ Weight calculation error: {weight_json.get('message', 'Unknown error')}")
                return

            weight_in_grams = weight_json["data"]["weight"]
            weight_text = f"{weight_in_grams:.2f}"

            # Update stored latest weight and UI label
            self.latest_weight = weight_text

            if self.saved_info_label:
                import re
                text = self.saved_info_label.text
                new_text = re.sub(
                    r"\[b\]Weight:\[/b\] \[color=00ffcc\].*?\[/color\] g",
                    f"[b]Weight:[/b] [color=00ffcc]{weight_text}[/color] g",
                    text,
                )
                self.saved_info_label.text = new_text
                self.saved_info_label.texture_update()

            print(f"✅ Weight updated: {weight_text} g")

        except Exception as e:
            print(f"❌ Exception during API flow: {e}")


class TilapiaApp(MDApp):
    def __init__(self, **kwargs):
        super().__init__(**kwargs)
        self.api_key = API_KEY  # Using hardcoded API key

    def build(self):
        self.theme_cls.theme_style = "Dark"
        self.theme_cls.primary_palette = "Yellow"

        root = MDBoxLayout(orientation="vertical", spacing=8, padding=8)

        # Header
        header = Label(text="Tilapia Growth Stage Detection", font_size=22, size_hint=(1, 0.06))
        root.add_widget(header)

        self.doc_field = MDTextField(
            hint_text="Enter DOC (e.g., DOC-20251117-04481)",
            helper_text="Copy the DOC from dashboard",
            helper_text_mode="on_focus",
            size_hint=(None, None),
            size=(300, 40),
            pos_hint={"center_x": 0.5},
        )

        root.add_widget(self.doc_field)

        # Camera widget info label (bottom of camera)
        self.info_label = Label(text="No detections yet.", size_hint=(1, 0.06))

        saved_frame_widget = Image()

        saved_info_card = MDCard(
            orientation="vertical",
            size_hint=(1, None),
            height=280,
            md_bg_color=(0.1, 0.1, 0.1, 0.9),
            radius=[20, 20, 20, 20],
            padding=15,
            elevation=8,
        )

        header_row = MDBoxLayout(orientation="horizontal", size_hint_y=None, height=40, spacing=10)
        saved_icon = MDIcon(icon="fish", size_hint=(None, None), size=(32, 32), theme_text_color="Custom", text_color=(1, 0.9, 0, 1))
        header_label = Label(text="[b][color=ffcc00][size=22]Detected Fish Info[/size][/color][/b]", markup=True, halign="left", valign="middle")
        header_row.add_widget(saved_icon)
        header_row.add_widget(header_label)

        saved_info_label = Label(text="[b][color=cccccc]No detections yet[/color][/b]", markup=True, halign="left", valign="top")
        saved_info_label.bind(size=saved_info_label.setter("text_size"))

        saved_info_card.add_widget(header_row)
        saved_info_card.add_widget(saved_info_label)

        # Camera widget itself
        self.camera_widget = KivyCamera(
            info_label=self.info_label,
            saved_frame_widget=saved_frame_widget,
            saved_info_label=saved_info_label,
            saved_icon=saved_icon,
            app_ref=self,
        )

        # Buttons row
        buttons_anchor = MDAnchorLayout(anchor_x="center", anchor_y="center", size_hint=(1, 0.08))
        buttons_row = MDBoxLayout(orientation="horizontal", spacing=20, adaptive_size=True)

        self.start_button = MDRaisedButton(text="Start Camera", md_bg_color=(0, 0.7, 0, 1), on_press=self.start_camera, size_hint=(None, None), size=(180, 50))
        self.stop_button = MDRaisedButton(text="Stop Camera", md_bg_color=(0.8, 0, 0, 1), on_press=self.stop_camera, size_hint=(None, None), size=(180, 50), disabled=True)
        self.weight_button = MDRaisedButton(text="Get Weight", md_bg_color=(0.2, 0.4, 1, 1), on_press=self.get_weight, size_hint=(None, None), size=(180, 50))

        buttons_row.add_widget(self.start_button)
        buttons_row.add_widget(self.stop_button)
        buttons_row.add_widget(self.weight_button)
        buttons_anchor.add_widget(buttons_row)

        # Main layout split horizontal
        main_split = MDBoxLayout(orientation="horizontal", size_hint=(1, 0.88), spacing=8)

        # Left column: camera, buttons, info label
        left_col = MDBoxLayout(orientation="vertical")
        left_col.add_widget(self.camera_widget)
        left_col.add_widget(buttons_anchor)
        left_col.add_widget(self.info_label)

        # Right column: saved frame image and info card
        right_col = MDBoxLayout(orientation="vertical", size_hint=(0.4, 1))
        right_col.add_widget(saved_frame_widget)
        right_col.add_widget(saved_info_card)

        main_split.add_widget(left_col)
        main_split.add_widget(right_col)

        root.add_widget(main_split)

        return root

    def start_camera(self, instance):
        self.camera_widget.start()
        self.start_button.disabled = True
        self.stop_button.disabled = False

    def stop_camera(self, instance):
        self.camera_widget.stop()
        self.start_button.disabled = False
        self.stop_button.disabled = True

    def get_weight(self, instance):
        doc_text = self.doc_field.text.strip()

        if not doc_text or not doc_text.startswith("DOC-"):
            Snackbar(text="Please enter a valid DOC number.").open()
            return

        self.camera_widget.fetch_weight_from_api(doc_text)



if __name__ == "__main__":
    TilapiaApp().run()