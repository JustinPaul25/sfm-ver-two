<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import { useFishDetection } from '@/composables/useFishDetection';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';

interface Detection {
  length: number;
  width: number;
  weight: number;
  stage: string;
  confidence: number;
  timestamp: string;
}

const props = defineProps<{
  samplingId?: number;
  doc?: string;
  autoDetect?: boolean;
}>();

const emit = defineEmits<{
  detection: [detection: Detection];
  error: [error: string];
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const stream = ref<MediaStream | null>(null);
const isStreaming = ref(false);
const detectionInterval = ref<number | null>(null);
const lastDetection = ref<Detection | null>(null);
const detectionHistory = ref<Detection[]>([]);

const {
  isDetecting,
  error: detectionError,
  detectionResults,
  detectFish,
  drawDetections,
} = useFishDetection();

const stageColors = {
  Starter: { bg: 'bg-green-50 dark:bg-green-900/20', text: 'text-green-700 dark:text-green-300', border: 'border-green-300 dark:border-green-700' },
  Grower: { bg: 'bg-yellow-50 dark:bg-yellow-900/20', text: 'text-yellow-700 dark:text-yellow-300', border: 'border-yellow-300 dark:border-yellow-700' },
  Finisher: { bg: 'bg-red-50 dark:bg-red-900/20', text: 'text-red-700 dark:text-red-300', border: 'border-red-300 dark:border-red-700' },
};

const stageColor = computed(() => {
  if (!lastDetection.value) return stageColors.Starter;
  return stageColors[lastDetection.value.stage as keyof typeof stageColors] || stageColors.Starter;
});

const startCamera = async () => {
  try {
    const constraints = {
      video: {
        width: { ideal: 1280 },
        height: { ideal: 720 },
        facingMode: 'environment', // Use back camera on mobile
      },
    };

    stream.value = await navigator.mediaDevices.getUserMedia(constraints);

    if (videoRef.value) {
      videoRef.value.srcObject = stream.value;
      videoRef.value.play();
      isStreaming.value = true;

      // Wait for video to be ready
      await new Promise((resolve) => {
        if (videoRef.value) {
          videoRef.value.onloadedmetadata = resolve;
        }
      });

      // Set canvas size to match video
      if (canvasRef.value && videoRef.value) {
        canvasRef.value.width = videoRef.value.videoWidth;
        canvasRef.value.height = videoRef.value.videoHeight;
      }

      // Start auto-detection if enabled
      if (props.autoDetect) {
        startAutoDetection();
      }
    }
  } catch (err) {
    const errorMessage = err instanceof Error ? err.message : 'Failed to access camera';
    emit('error', errorMessage);
    console.error('Camera error:', err);
  }
};

const stopCamera = () => {
  if (stream.value) {
    stream.value.getTracks().forEach((track) => track.stop());
    stream.value = null;
  }

  if (videoRef.value) {
    videoRef.value.srcObject = null;
  }

  stopAutoDetection();
  isStreaming.value = false;
};

const startAutoDetection = () => {
  if (detectionInterval.value) return;

  detectionInterval.value = window.setInterval(() => {
    performDetection();
  }, 3000); // Detect every 3 seconds
};

const stopAutoDetection = () => {
  if (detectionInterval.value) {
    clearInterval(detectionInterval.value);
    detectionInterval.value = null;
  }
};

const performDetection = async () => {
  if (!videoRef.value || !canvasRef.value || isDetecting.value) return;

  try {
    // Draw current video frame to canvas
    const ctx = canvasRef.value.getContext('2d');
    if (ctx) {
      ctx.drawImage(videoRef.value, 0, 0, canvasRef.value.width, canvasRef.value.height);
    }

    // Perform detection
    const detections = await detectFish(videoRef.value);

    if (detections.length > 0) {
      // Use the first detection (most confident)
      const detection = detections[0];

      const detectionData: Detection = {
        length: detection.length,
        width: detection.width,
        weight: detection.weight,
        stage: detection.stage,
        confidence: detection.boundingBox.confidence,
        timestamp: new Date().toISOString(),
      };

      lastDetection.value = detectionData;
      detectionHistory.value.unshift(detectionData);

      // Keep only last 10 detections
      if (detectionHistory.value.length > 10) {
        detectionHistory.value.pop();
      }

      // Emit detection event
      emit('detection', detectionData);

      // Draw detections on canvas
      drawDetections(canvasRef.value, detections);
    }
  } catch (err) {
    console.error('Detection error:', err);
  }
};

const captureFrame = async () => {
  await performDetection();
};

// Watch for errors from the composable
watch(detectionError, (newError) => {
  if (newError) {
    emit('error', newError);
  }
});

onMounted(() => {
  // Auto-start camera if autoDetect is enabled
  if (props.autoDetect) {
    startCamera();
  }
});

onBeforeUnmount(() => {
  stopCamera();
});

defineExpose({
  startCamera,
  stopCamera,
  captureFrame,
  lastDetection,
  detectionHistory,
});
</script>

<template>
  <div class="flex flex-col gap-4">
    <!-- Camera Feed -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center justify-between">
          <span>Fish Detection Camera</span>
          <div class="flex gap-2">
            <Button
              v-if="!isStreaming"
              @click="startCamera"
              variant="default"
              size="sm"
            >
              📹 Start Camera
            </Button>
            <Button
              v-else
              @click="stopCamera"
              variant="destructive"
              size="sm"
            >
              ⏹ Stop Camera
            </Button>
            <Button
              v-if="isStreaming"
              @click="captureFrame"
              variant="secondary"
              size="sm"
              :disabled="isDetecting"
            >
              📸 Capture & Detect
            </Button>
          </div>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="relative w-full aspect-video bg-gray-900 rounded-lg overflow-hidden">
          <!-- Video Element -->
          <video
            ref="videoRef"
            class="absolute inset-0 w-full h-full object-contain"
            autoplay
            playsinline
            muted
          />
          
          <!-- Canvas for Drawing Detections -->
          <canvas
            ref="canvasRef"
            class="absolute inset-0 w-full h-full object-contain pointer-events-none"
          />
          
          <!-- Status Overlay -->
          <div
            v-if="!isStreaming"
            class="absolute inset-0 flex items-center justify-center bg-gray-900/80 text-white"
          >
            <div class="text-center">
              <p class="text-lg mb-2">📹 Camera is not active</p>
              <p class="text-sm text-gray-400">Click "Start Camera" to begin detection</p>
            </div>
          </div>
          
          <!-- Detection Indicator -->
          <div
            v-if="isDetecting"
            class="absolute top-4 right-4 bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-medium animate-pulse"
          >
            🔍 Detecting...
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Detection Results -->
    <Card v-if="lastDetection" :class="stageColor.bg">
      <CardHeader>
        <CardTitle class="flex items-center justify-between">
          <span :class="stageColor.text">Latest Detection</span>
          <span
            :class="`${stageColor.text} ${stageColor.border} border px-3 py-1 rounded-full text-sm font-medium`"
          >
            {{ lastDetection.stage }}
          </span>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <div class="text-sm" :class="stageColor.text">Length</div>
            <div class="text-2xl font-bold" :class="stageColor.text">
              {{ lastDetection.length.toFixed(1) }} cm
            </div>
          </div>
          <div>
            <div class="text-sm" :class="stageColor.text">Width</div>
            <div class="text-2xl font-bold" :class="stageColor.text">
              {{ lastDetection.width.toFixed(1) }} cm
            </div>
          </div>
          <div>
            <div class="text-sm" :class="stageColor.text">Weight</div>
            <div class="text-2xl font-bold" :class="stageColor.text">
              {{ lastDetection.weight.toFixed(1) }} g
            </div>
          </div>
          <div>
            <div class="text-sm" :class="stageColor.text">Confidence</div>
            <div class="text-2xl font-bold" :class="stageColor.text">
              {{ (lastDetection.confidence * 100).toFixed(0) }}%
            </div>
          </div>
        </div>
        <div class="mt-3 text-xs" :class="stageColor.text">
          Detected at: {{ new Date(lastDetection.timestamp).toLocaleString() }}
        </div>
      </CardContent>
    </Card>

    <!-- Detection History -->
    <Card v-if="detectionHistory.length > 0">
      <CardHeader>
        <CardTitle>Detection History</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="px-3 py-2 text-left">Time</th>
                <th class="px-3 py-2 text-left">Length (cm)</th>
                <th class="px-3 py-2 text-left">Width (cm)</th>
                <th class="px-3 py-2 text-left">Weight (g)</th>
                <th class="px-3 py-2 text-left">Stage</th>
                <th class="px-3 py-2 text-left">Confidence</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(det, index) in detectionHistory"
                :key="index"
                class="border-b border-gray-200 dark:border-gray-700"
              >
                <td class="px-3 py-2">{{ new Date(det.timestamp).toLocaleTimeString() }}</td>
                <td class="px-3 py-2">{{ det.length.toFixed(1) }}</td>
                <td class="px-3 py-2">{{ det.width.toFixed(1) }}</td>
                <td class="px-3 py-2">{{ det.weight.toFixed(1) }}</td>
                <td class="px-3 py-2">
                  <span
                    :class="`${stageColors[det.stage as keyof typeof stageColors].border} ${stageColors[det.stage as keyof typeof stageColors].text} border px-2 py-0.5 rounded text-xs`"
                  >
                    {{ det.stage }}
                  </span>
                </td>
                <td class="px-3 py-2">{{ (det.confidence * 100).toFixed(0) }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<style scoped>
video {
  transform: scaleX(-1); /* Mirror the video for a more natural selfie view */
}

canvas {
  transform: scaleX(-1); /* Mirror the canvas to match the video */
}
</style>
