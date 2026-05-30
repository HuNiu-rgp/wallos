<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Categories/Index', [
            'categories' => $request->user()
                ->categories()
                ->with('parent:id,name')
                ->withCount('subscriptions')
                ->orderBy('type')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where('user_id', $request->user()->id)->where('type', $request->input('type')),
            ],
            'type' => ['required', 'in:income,expense'],
            'parent_id' => ['nullable', Rule::exists('categories', 'id')->where('user_id', $request->user()->id)],
            'color' => ['nullable', 'string', 'max:32'],
            'icon' => ['nullable', 'string', 'max:64'],
        ]);

        $request->user()->categories()->create($validated);

        return back()->with('success', __('Category created.'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        abort_unless($category->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where('user_id', $request->user()->id)->where('type', $request->input('type'))->ignore($category->id),
            ],
            'type' => ['required', 'in:income,expense'],
            'parent_id' => ['nullable', Rule::exists('categories', 'id')->where('user_id', $request->user()->id)],
            'color' => ['nullable', 'string', 'max:32'],
            'icon' => ['nullable', 'string', 'max:64'],
        ]);

        abort_if((int) ($validated['parent_id'] ?? 0) === $category->id, 422, __('A category cannot be its own parent.'));

        $category->update($validated);

        return back()->with('success', __('Category updated.'));
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        abort_unless($category->user_id === $request->user()->id, 404);
        abort_if($category->subscriptions()->exists(), 422, __('Categories in use cannot be deleted.'));

        $category->delete();

        return back()->with('success', __('Category deleted.'));
    }
}
