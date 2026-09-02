<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodBridge - Donor Dashboard</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:wght@600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'DM Sans', sans-serif;
        }

        .font-heading {
            font-family: 'Fraunces', serif;
        }

        .tag-available {
            background: #dcfce7;
            color: #166534;
        }

        .tag-expiring_soon {
            background: #fef3c7;
            color: #92400e;
        }

        .tag-expired {
            background: #fee2e2;
            color: #991b1b;
        }

        .tag-reserved {
            background: #dbeafe;
            color: #1e40af;
        }

        .tag-completed {
            background: #e5e7eb;
            color: #374151;
        }

        .tag-cancelled {
            background: #f3f4f6;
            color: #6b7280;
        }
    </style>
</head>

<body class="w-full min-h-screen bg-gray-50">

    <header class="w-full border-b border-green-100 bg-white">
        <nav class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
            <h1 class="font-heading text-2xl font-bold">FoodBridge</h1>
            <a href="<?php echo e(route('home')); ?>"
                class="px-5 py-2.5 rounded-full font-medium transition hover:opacity-90 bg-gray-900 text-white">Logout</a>
        </nav>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-10">

        
        <?php if(session('success')): ?>
            <div id="success-alert"
                class="mb-8 p-4 bg-green-100 text-green-800 rounded-lg flex items-center gap-2 transition-opacity duration-700">
                <i data-lucide="check-circle" class="w-5 h-5"></i> <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>

        
        <section class="flex items-center gap-6 mb-12">
            <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center shadow-md">
                <i data-lucide="user" class="w-10 h-10 text-green-700"></i>
            </div>
            <div>
                <h2 class="font-heading font-bold text-2xl">Welcome back, Donor</h2>
                <p class="mt-1 text-gray-600">Thank you for supporting FoodBridge!</p>
            </div>
        </section>

        
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-14">
            <div class="bg-white rounded-xl p-6 text-center shadow-sm">
                <p class="font-heading text-3xl font-bold"><?php echo e($stats['total_donations']); ?></p>
                <p class="mt-1 text-gray-600">Total Donations Listed</p>
            </div>
            <div class="bg-white rounded-xl p-6 text-center shadow-sm">
                <p class="font-heading text-3xl font-bold"><?php echo e(number_format($stats['total_quantity'], 1)); ?></p>
                <p class="mt-1 text-gray-600">Total Quantity Donated</p>
            </div>
            <div class="bg-white rounded-xl p-6 text-center shadow-sm">
                <p class="font-heading text-3xl font-bold"><?php echo e($stats['active_listings']); ?></p>
                <p class="mt-1 text-gray-600">Active Listings</p>
            </div>
        </section>

        
        <section class="mb-14">
            <div class="flex items-center justify-between mb-6">
                <h2 class="font-heading font-bold text-xl">Your Donations</h2>

                <a href="<?php echo e(route('donor.donations.history')); ?>"
                    class="inline-flex items-center text-sm px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition">
                    <i data-lucide="history" class="w-4 h-4 mr-1"></i> View Donation History
                </a>
            </div>

            <?php if($donations->isEmpty()): ?>
                <p class="text-gray-500">You haven't listed any donations yet. Use the form below to get started.</p>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php $__currentLoopData = $donations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $donation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="bg-white rounded-xl overflow-hidden shadow-md">
                            <div class="w-full h-40 bg-gray-100 flex items-center justify-center">
                                <?php if($donation->image_url): ?>
                                    <img src="<?php echo e($donation->image_url); ?>" class="w-full h-full object-cover"
                                        loading="lazy">
                                <?php else: ?>
                                    <i data-lucide="image" class="w-10 h-10 text-gray-300"></i>
                                <?php endif; ?>
                            </div>
                            <div class="p-5">
                                <h3 class="font-semibold"><?php echo e($donation->food_name); ?></h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    <?php echo e($donation->quantity); ?> <?php echo e($donation->unit); ?> &middot;
                                    <?php echo e($donation->category->name ?? '—'); ?>

                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    Expires: <?php echo e($donation->expiry_date->format('d M Y, h:i A')); ?>

                                </p>
                                <span
                                    class="tag-<?php echo e($donation->status); ?> inline-block mt-3 px-3 py-1 rounded-full text-xs font-semibold">
                                    <?php echo e(ucfirst(str_replace('_', ' ', $donation->status))); ?>

                                </span>

                                
                                <div class="mt-4 flex gap-2">
                                    <a href="<?php echo e(route('donor.donations.edit', $donation->id)); ?>"
                                        class="flex-1 text-center text-sm px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition">
                                        <i data-lucide="pencil" class="inline w-4 h-4 mr-1"></i> Edit
                                    </a>

                                    <form action="<?php echo e(route('donor.donations.destroy', $donation->id)); ?>" method="POST"
                                        class="flex-1"
                                        onsubmit="return confirm('Delete this donation listing? This cannot be undone.');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit"
                                            class="w-full text-sm px-3 py-2 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 transition">
                                            <i data-lucide="trash-2" class="inline w-4 h-4 mr-1"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php if($stats['total_donations'] > 6): ?>
                    <div class="text-center mt-8">
                        <a href="<?php echo e(route('donor.donations.all')); ?>"
                            class="inline-block px-6 py-2.5 rounded-full font-medium border border-gray-300 hover:bg-gray-50 transition">
                            View More <i data-lucide="arrow-right" class="inline w-4 h-4 ml-1"></i>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        
        <section class="bg-white rounded-2xl p-8 mb-14 shadow-sm">
            <h2 class="font-heading font-bold text-xl mb-6">List a New Donation</h2>

            <form method="POST" action="<?php echo e(route('donor.donations.store')); ?>" enctype="multipart/form-data"
                class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <?php echo csrf_field(); ?>

                <div>
                    <label for="food_name" class="block font-medium mb-2">Food Item</label>
                    <input id="food_name" name="food_name" type="text" placeholder="e.g. Fresh bread loaves"
                        value="<?php echo e(old('food_name')); ?>"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        required>
                    <?php $__errorArgs = ['food_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label for="donation_type" class="block font-medium mb-2">Food Type</label>
                    <select id="donation_type" name="donation_type"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        required>
                        <option value="">Select type</option>
                        <option value="cooked_food">Cooked Food</option>
                        <option value="fresh_produce">Fresh Produce</option>
                        <option value="packaged_goods">Packaged Goods</option>
                    </select>
                    <?php $__errorArgs = ['donation_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label for="category_id" class="block font-medium mb-2">Category</label>
                    <select id="category_id" name="category_id"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        required>
                        <option value="">Select category</option>
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($category->id); ?>"><?php echo e($category->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="quantity" class="block font-medium mb-2">Quantity</label>
                        <input id="quantity" name="quantity" type="number" step="0.01" placeholder="e.g. 20"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            required>
                        <?php $__errorArgs = ['quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="unit" class="block font-medium mb-2">Unit</label>
                        <input id="unit" name="unit" type="text" placeholder="e.g. packs, kg"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                            required>
                        <?php $__errorArgs = ['unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div>
                    <label for="expiry_date" class="block font-medium mb-2">Expiry Date (optional)</label>
                    <input id="expiry_date" name="expiry_date" type="datetime-local"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <p class="text-xs text-gray-400 mt-1">Leave blank to auto-assign based on food type.</p>
                </div>

                <div>
                    <label for="pickup_address" class="block font-medium mb-2">Pickup Address</label>
                    <input id="pickup_address" name="pickup_address" type="text"
                        placeholder="e.g. 12, Jalan Bunga Raya, Melaka"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        required>
                    <?php $__errorArgs = ['pickup_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="md:col-span-2">
                    <label for="photo" class="block font-medium mb-2">Photo (optional)</label>
                    <input id="photo" name="photo" type="file" accept="image/*"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <?php $__errorArgs = ['photo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="text-red-600 text-sm mt-1"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="flex items-end md:col-span-2">
                    <button type="submit"
                        class="w-full py-2.5 rounded-lg font-semibold transition hover:opacity-90 bg-green-600 text-white">
                        List Donation
                    </button>
                </div>
            </form>
        </section>

    </main>

    <footer class="w-full py-8 px-6 text-center bg-gray-900 text-gray-400">
        <p>&copy; <?php echo e(date('Y')); ?> FoodBridge. Supporting SDG 2: Zero Hunger.</p>
    </footer>

    <script>
        lucide.createIcons();

        // Auto-dismiss success alert after 5 seconds
        const successAlert = document.getElementById('success-alert');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 700); // 等淡出动画跑完再真正移除
            }, 3000);
        }
    </script>
</body>

</html>
<?php /**PATH C:\xampp\htdocs\Donation Management\resources\views/donor/dashboard.blade.php ENDPATH**/ ?>