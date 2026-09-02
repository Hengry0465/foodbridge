<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FoodBridge - All Donations</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'DM Sans', sans-serif; }
    .font-heading { font-family: 'Fraunces', serif; }
    .tag-available { background:#dcfce7; color:#166534; }
    .tag-expiring_soon { background:#fef3c7; color:#92400e; }
    .tag-expired { background:#fee2e2; color:#991b1b; }
    .tag-reserved { background:#dbeafe; color:#1e40af; }
    .tag-completed { background:#e5e7eb; color:#374151; }
  </style>
</head>
<body class="w-full min-h-screen bg-gray-50">

  <header class="w-full border-b border-green-100 bg-white">
    <nav class="max-w-7xl mx-auto px-6 py-5 flex items-center justify-between">
      <h1 class="font-heading text-2xl font-bold">FoodBridge</h1>
      <a href="<?php echo e(route('donor.dashboard')); ?>" class="px-5 py-2.5 rounded-full font-medium transition hover:opacity-90 bg-gray-900 text-white">
        Back to Dashboard
      </a>
    </nav>
  </header>

  <main class="max-w-7xl mx-auto px-6 py-10">
    <h2 class="font-heading font-bold text-2xl mb-8">All Your Donations</h2>

    <?php if($donations->isEmpty()): ?>
      <p class="text-gray-500">You haven't listed any donations yet.</p>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <?php $__currentLoopData = $donations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $donation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="bg-white rounded-xl overflow-hidden shadow-md">
            <div class="w-full h-40 bg-gray-100 flex items-center justify-center">
              <?php if($donation->image_url): ?>
                <img src="<?php echo e($donation->image_url); ?>" class="w-full h-full object-cover" loading="lazy">
              <?php else: ?>
                <i data-lucide="image" class="w-10 h-10 text-gray-300"></i>
              <?php endif; ?>
            </div>
            <div class="p-5">
              <h3 class="font-semibold"><?php echo e($donation->food_name); ?></h3>
              <p class="mt-1 text-sm text-gray-600">
                <?php echo e($donation->quantity); ?> <?php echo e($donation->unit); ?> &middot; <?php echo e($donation->category->name ?? '—'); ?>

              </p>
              <p class="mt-1 text-xs text-gray-400">
                Expires: <?php echo e($donation->expiry_date->format('d M Y, h:i A')); ?>

              </p>
              <span class="tag-<?php echo e($donation->status); ?> inline-block mt-3 px-3 py-1 rounded-full text-xs font-semibold">
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

      
      <div class="flex justify-center">
        <?php echo e($donations->links()); ?>

      </div>
    <?php endif; ?>
  </main>

  <footer class="w-full py-8 px-6 text-center bg-gray-900 text-gray-400 mt-14">
    <p>&copy; <?php echo e(date('Y')); ?> FoodBridge. Supporting SDG 2: Zero Hunger.</p>
  </footer>

  <script>lucide.createIcons();</script>
</body>
</html><?php /**PATH C:\xampp\htdocs\Donation Management\resources\views/donor/all-donations.blade.php ENDPATH**/ ?>