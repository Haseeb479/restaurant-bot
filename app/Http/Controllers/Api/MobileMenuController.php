<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class MobileMenuController extends Controller
{
    private const ALLOWED_MENU_EXTENSIONS = [
        'pdf', 'docx', 'doc', 'xlsx', 'xls', 'csv', 'txt', 'tsv',
        'jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif', 'jpe', 'bmp',
    ];

    /**
     * List all categories and menu items with variants.
     */
    public function index(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        $categories = $restaurant->categories()->with(['menuItems.variants'])->get();
        $uncategorized = $restaurant->menuItems()->whereNull('category_id')->with('variants')->get();

        return response()->json([
            'success'       => true,
            'categories'    => $categories,
            'uncategorized' => $uncategorized,
            'menu_file'     => $restaurant->menu_file ? asset($restaurant->menu_file) : null,
            'menu_file_name'=> $restaurant->menu_file_name,
            'menu_file_type'=> $restaurant->menu_file_type,
        ]);
    }

    /**
     * Create a new category.
     */
    public function storeCategory(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $category = $restaurant->categories()->create([
            'name'       => trim($validated['name']),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return response()->json([
            'success'  => true,
            'category' => $category,
            'message'  => 'Category created successfully.',
        ], 201);
    }

    /**
     * Create a new menu item.
     */
    public function storeItem(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        if ($restaurant->hasExceededMenuItems()) {
            return response()->json([
                'success' => false,
                'message' => "Menu item limit reached ({$restaurant->maxMenuItems()} items). Upgrade plan to add more.",
            ], 422);
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('restaurant_id', $restaurant->id)],
            'price'       => 'required|numeric|min:0|max:1000000',
            'description' => 'nullable|string|max:1000',
        ]);

        $item = $restaurant->menuItems()->create([
            'category_id' => $validated['category_id'] ?? null,
            'name'        => trim($validated['name']),
            'price'       => (float) $validated['price'],
            'description' => $validated['description'] ?? null,
            'is_available'=> true,
        ]);

        return response()->json([
            'success' => true,
            'item'    => $item->fresh('variants'),
            'message' => 'Menu item created successfully.',
        ], 201);
    }

    /**
     * Toggle item availability.
     */
    public function toggleItem(Request $request, $itemId)
    {
        $restaurant = $request->attributes->get('restaurant');
        $item = $restaurant->menuItems()->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }

        $item->is_available = !$item->is_available;
        $item->save();

        return response()->json([
            'success'      => true,
            'is_available' => (bool) $item->is_available,
            'message'      => $item->is_available ? 'Item marked available.' : 'Item marked sold out.',
        ]);
    }

    /**
     * Update item details (name, price, description).
     */
    public function updateItem(Request $request, $itemId)
    {
        $restaurant = $request->attributes->get('restaurant');
        $item = $restaurant->menuItems()->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }

        $validated = $request->validate([
            'name'         => 'sometimes|string|max:150',
            'price'        => 'sometimes|numeric|min:0',
            'description'  => 'nullable|string|max:1000',
            'is_available' => 'sometimes|boolean',
            'category_id'  => ['nullable', 'integer', Rule::exists('categories', 'id')->where('restaurant_id', $restaurant->id)],
        ]);

        $item->update($validated);

        return response()->json([
            'success' => true,
            'item'    => $item->fresh('variants'),
            'message' => 'Item updated successfully.',
        ]);
    }

    /**
     * Delete a menu item.
     */
    public function deleteItem(Request $request, $itemId)
    {
        $restaurant = $request->attributes->get('restaurant');
        $item = $restaurant->menuItems()->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu item deleted successfully.',
        ]);
    }

    /**
     * Upload Menu File (CSV / Excel / Image / PDF).
     * If CSV / Excel is uploaded, automatically extracts and saves items.
     * If Image / PDF is uploaded, saves as restaurant menu card flyer for WhatsApp bot.
     */
    public function uploadMenuFile(Request $request)
    {
        $restaurant = $request->attributes->get('restaurant');

        $request->validate([
            'file' => 'required|file|max:20480', // 20MB limit
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === '') {
            $extension = strtolower((string) $file->guessExtension());
        }

        if (!in_array($extension, self::ALLOWED_MENU_EXTENSIONS, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported file format. Supported: CSV, Excel, Image (JPG/PNG), PDF.',
            ], 422);
        }

        $destPath = public_path('uploads/menus');
        if (!is_dir($destPath)) {
            @mkdir($destPath, 0777, true);
        }

        $fileName = 'menu_' . $restaurant->id . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $file->move($destPath, $fileName);
        $relativePath = 'uploads/menus/' . $fileName;

        $fileType = 'document';
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], true)) {
            $fileType = 'image';
        } elseif ($extension === 'pdf') {
            $fileType = 'pdf';
        } elseif (in_array($extension, ['xls', 'xlsx', 'csv', 'tsv', 'txt'], true)) {
            $fileType = 'excel';
        }

        $updateData = [
            'menu_file'      => $relativePath,
            'menu_file_name' => $file->getClientOriginalName(),
            'menu_file_type' => $fileType,
        ];

        if ($fileType === 'image') {
            $updateData['menu_image'] = $relativePath;
        }

        $importedCount = 0;
        if ($fileType === 'excel') {
            if ($request->boolean('replace_menu')) {
                $restaurant->menuItems()->delete();
                $restaurant->categories()->delete();
            }
            $importedCount = $this->importCsvFile($restaurant, $destPath . DIRECTORY_SEPARATOR . $fileName);
        }

        $restaurant->update($updateData);

        return response()->json([
            'success'        => true,
            'file_type'      => $fileType,
            'file_url'       => asset($relativePath),
            'imported_items' => $importedCount,
            'message'        => $fileType === 'excel'
                ? "Successfully imported {$importedCount} menu items from spreadsheet!"
                : "Menu file uploaded successfully! It is now connected to your WhatsApp bot.",
        ]);
    }

    /**
     * Simple robust CSV parser for mobile upload.
     */
    private function importCsvFile(Restaurant $restaurant, string $fullPath): int
    {
        $rows = [];
        if (($handle = fopen($fullPath, 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        if (empty($rows)) return 0;

        $imported = 0;
        $currentCat = 'General';
        $categoryCache = [];

        foreach ($rows as $idx => $row) {
            if ($idx === 0) continue; // skip header
            if (!is_array($row) || empty($row)) continue;

            $catName = !empty($row[0]) ? trim($row[0]) : $currentCat;
            $itemName = !empty($row[1]) ? trim($row[1]) : '';
            $price = !empty($row[2]) ? (float) preg_replace('/[^0-9.]/', '', (string)$row[2]) : 0;
            $desc = !empty($row[3]) ? trim($row[3]) : null;

            if (empty($itemName)) continue;

            $catKey = strtolower($catName);
            if (!isset($categoryCache[$catKey])) {
                $category = $restaurant->categories()->firstOrCreate(
                    ['name' => $catName],
                    ['sort_order' => count($categoryCache) + 1]
                );
                $categoryCache[$catKey] = $category->id;
            }
            $categoryId = $categoryCache[$catKey];

            $restaurant->menuItems()->updateOrCreate(
                [
                    'category_id' => $categoryId,
                    'name'        => $itemName,
                ],
                [
                    'price'        => $price,
                    'description'  => $desc,
                    'is_available' => true,
                ]
            );

            $imported++;
        }

        return $imported;
    }
}
