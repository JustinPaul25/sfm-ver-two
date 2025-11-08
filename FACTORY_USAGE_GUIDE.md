# Laravel Factory Usage in Seeders - Complete Guide

## Basic Factory Usage

### 1. Create Single Record
```php
// Create one record with factory defaults
User::factory()->create();

// Create one record with custom attributes
User::factory()->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);
```

### 2. Create Multiple Records
```php
// Create 10 records with factory defaults
User::factory(10)->create();

// Create 5 records with custom attributes
User::factory(5)->create([
    'status' => 'active',
]);
```

### 3. Create Records Without Saving
```php
// Make records without saving to database
$users = User::factory(3)->make();

// Make one record with custom attributes
$user = User::factory()->make([
    'name' => 'Jane Doe',
]);
```

## Advanced Factory Usage

### 4. Using Sequences
```php
// Create records with sequential values
User::factory(5)->sequence(
    fn ($sequence) => ['name' => 'User ' . ($sequence->index + 1)]
)->create();

// Multiple sequence fields
User::factory(3)->sequence(
    ['name' => 'Alice', 'email' => 'alice@example.com'],
    ['name' => 'Bob', 'email' => 'bob@example.com'],
    ['name' => 'Charlie', 'email' => 'charlie@example.com'],
)->create();
```

### 5. Using States
```php
// Create records with specific states
User::factory()->admin()->create();
User::factory()->inactive()->create();

// Multiple states
User::factory()->admin()->verified()->create();
```

### 6. Using Relationships
```php
// Create records with relationships
Post::factory(3)->create()->each(function ($post) {
    Comment::factory(5)->create([
        'post_id' => $post->id,
    ]);
});

// Using factory relationships
Post::factory(3)->has(
    Comment::factory(5)
)->create();
```

### 7. Using Callbacks
```php
// After creating callback
User::factory(3)->create()->each(function ($user) {
    Profile::factory()->create([
        'user_id' => $user->id,
    ]);
});

// After making callback
User::factory(3)->make()->each(function ($user) {
    // Do something with the model before saving
});
```

## Real-World Examples

### Example 1: E-commerce Products
```php
// Create categories with products
Category::factory(5)->create()->each(function ($category) {
    Product::factory(rand(3, 8))->create([
        'category_id' => $category->id,
    ])->each(function ($product) {
        // Create product images
        ProductImage::factory(rand(1, 4))->create([
            'product_id' => $product->id,
        ]);
    });
});
```

### Example 2: Blog System
```php
// Create users with posts and comments
User::factory(10)->create()->each(function ($user) {
    Post::factory(rand(1, 5))->create([
        'user_id' => $user->id,
    ])->each(function ($post) {
        Comment::factory(rand(0, 10))->create([
            'post_id' => $post->id,
            'user_id' => User::inRandomOrder()->first()->id,
        ]);
    });
});
```

### Example 3: Feed Consumption (Your Use Case)
```php
// Create cages with realistic feed consumption data
Cage::factory(5)->create()->each(function ($cage) {
    // Create 90 days of feed consumption with realistic patterns
    for ($day = 1; $day <= 90; $day++) {
        // Feed amount increases over time (fish grow)
        $baseAmount = 2.0 + ($day * 0.02);
        $variation = rand(-10, 10) / 100;
        $feedAmount = max(1.0, $baseAmount + $variation);

        CageFeedConsumption::factory()->create([
            'cage_id' => $cage->id,
            'day_number' => $day,
            'feed_amount' => round($feedAmount, 2),
            'consumption_date' => now()->subDays(90 - $day),
            'notes' => $day % 7 === 0 ? 'Weekly feeding review' : null,
        ]);
    }
});
```

## Running Seeders

### Run All Seeders
```bash
php artisan db:seed
```

### Run Specific Seeder
```bash
php artisan db:seed --class=UserSeeder
```

### Run Multiple Seeders
```bash
php artisan db:seed --class=UserSeeder --class=PostSeeder
```

### Fresh Database with Seeding
```bash
php artisan migrate:fresh --seed
```

## Best Practices

1. **Use Factories for Test Data**: Factories are perfect for generating realistic test data
2. **Keep Seeders Focused**: Each seeder should handle one model or related models
3. **Use Sequences for Unique Data**: Use sequences when you need unique values
4. **Handle Relationships Properly**: Make sure foreign keys are set correctly
5. **Use Realistic Data**: Generate data that makes sense for your application
6. **Avoid Duplicates**: Use `firstOrCreate()` when you might run seeders multiple times

## Common Patterns

### Pattern 1: Conditional Creation
```php
// Only create if doesn't exist
User::firstOrCreate(
    ['email' => 'admin@example.com'],
    User::factory()->make()->toArray()
);
```

### Pattern 2: Batch Creation
```php
// Create in batches for better performance
User::factory(1000)->create(); // This might be slow
User::factory(1000)->createInBatches(100); // Better performance
```

### Pattern 3: Custom Factory Methods
```php
// In your factory
public function admin()
{
    return $this->state([
        'role' => 'admin',
        'is_admin' => true,
    ]);
}

// In your seeder
User::factory()->admin()->create();
```

This guide covers all the common patterns for using factories in Laravel seeders. Choose the approach that best fits your specific use case! 