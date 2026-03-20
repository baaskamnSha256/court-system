<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatterCategory;
use Illuminate\Http\Request;

class MatterCategoriesController extends Controller
{
    public function index(Request $request)
    {
        $query = MatterCategory::query()->orderBy('sort_order')->orderBy('name');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where('name', 'like', '%' . $q . '%');
        }

        $matterCategories = $query->get();
        $total = MatterCategory::count();

        return view('admin.matter-categories.index', compact('matterCategories', 'total'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $maxOrder = MatterCategory::max('sort_order') ?? 0;
        MatterCategory::create([
            'name' => $data['name'],
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('admin.matter-categories.index')
            ->with('success', 'Зүйл анги нэмэгдлээ.');
    }

    public function destroy(MatterCategory $matterCategory)
    {
        $matterCategory->delete();
        return redirect()->route('admin.matter-categories.index')
            ->with('success', 'Зүйл анги устгагдлаа.');
    }
}
