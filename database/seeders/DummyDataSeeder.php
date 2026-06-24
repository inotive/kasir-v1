<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\AddonCategory;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Multitenancy\Landlord;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->createTenantWithOwner(
            name: 'Tenant 1',
            slug: 'tenant1',
            ownerName: 'owner1',
            ownerEmail: 'owner1@tenant1.com',
            prefix: 'T1',
        );

        $this->createTenantWithOwner(
            name: 'Tenant 2',
            slug: 'tenant2',
            ownerName: 'owner2',
            ownerEmail: 'owner2@tenant2.com',
            prefix: 'T2',
        );

        $this->createSuperAdminData();
    }

    private function createTenantWithOwner(
        string $name,
        string $slug,
        string $ownerName,
        string $ownerEmail,
        string $prefix,
    ): void {
        if (Tenant::query()->where('slug', $slug)->exists()) {
            $this->command?->warn("Tenant '{$slug}' already exists, skipping.");

            return;
        }

        $tenant = Tenant::query()->create([
            'name' => $name,
            'slug' => $slug,
            'business_name' => $name,
            'domain' => $slug.'.'.parse_url(config('app.url'), PHP_URL_HOST),
        ]);

        $tenant->execute(function () use ($prefix) {
            $catA = Category::query()->create(['name' => "{$prefix} - Kategori A"]);
            $catB = Category::query()->create(['name' => "{$prefix} - Kategori B"]);

            $prodA = Product::query()->create([
                'name' => "{$prefix} - Produk A",
                'description' => "Produk pertama milik {$prefix}",
                'category_id' => $catA->id,
                'image' => '',
            ]);
            ProductVariant::query()->create([
                'product_id' => $prodA->id,
                'name' => 'Reguler',
                'price' => 15000,
            ]);

            $prodB = Product::query()->create([
                'name' => "{$prefix} - Produk B",
                'description' => "Produk kedua milik {$prefix}",
                'category_id' => $catA->id,
                'image' => '',
            ]);
            ProductVariant::query()->create([
                'product_id' => $prodB->id,
                'name' => 'Reguler',
                'price' => 20000,
            ]);

            $prodC = Product::query()->create([
                'name' => "{$prefix} - Produk C",
                'description' => "Produk ketiga milik {$prefix}",
                'category_id' => $catB->id,
                'image' => '',
            ]);
            ProductVariant::query()->create([
                'product_id' => $prodC->id,
                'name' => 'Reguler',
                'price' => 10000,
            ]);

            $opsi = AddonCategory::query()->create(['name' => "{$prefix} - Opsi"]);

            $tambah1 = Addon::query()->create([
                'addon_category_id' => $opsi->id,
                'name' => "{$prefix} - Tambahan 1",
                'price' => 3000,
            ]);
            $tambah2 = Addon::query()->create([
                'addon_category_id' => $opsi->id,
                'name' => "{$prefix} - Tambahan 2",
                'price' => 5000,
            ]);

            $prodA->addons()->attach([$tambah1->id, $tambah2->id]);
            $prodB->addons()->attach([$tambah1->id, $tambah2->id]);
        });

        if (! User::query()->where('email', $ownerEmail)->exists()) {
            $user = User::query()->create([
                'name' => $ownerName,
                'email' => $ownerEmail,
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
                'role' => 'owner',
                'is_active' => true,
            ]);

            $user->assignRole('owner');
        } else {
            $this->command?->warn("User '{$ownerEmail}' already exists, skipping.");
        }
    }

    private function createSuperAdminData(): void
    {
        if (Category::query()->where('name', 'Super - Kategori')->exists()) {
            $this->command?->warn('Superadmin data already exists, skipping.');

            return;
        }

        Landlord::execute(function () {
            $cat = Category::query()->create(['name' => 'Super - Kategori']);

            $p1 = Product::query()->create([
                'name' => 'Super - Produk 1',
                'description' => 'Produk superadmin pertama',
                'category_id' => $cat->id,
                'image' => '',
            ]);
            ProductVariant::query()->create([
                'product_id' => $p1->id,
                'name' => 'Reguler',
                'price' => 25000,
            ]);

            $p2 = Product::query()->create([
                'name' => 'Super - Produk 2',
                'description' => 'Produk superadmin kedua',
                'category_id' => $cat->id,
                'image' => '',
            ]);
            ProductVariant::query()->create([
                'product_id' => $p2->id,
                'name' => 'Reguler',
                'price' => 30000,
            ]);

            $opsi = AddonCategory::query()->create(['name' => 'Super - Opsi']);

            Addon::query()->create([
                'addon_category_id' => $opsi->id,
                'name' => 'Super - Tambahan',
                'price' => 10000,
            ]);
        });
    }
}
