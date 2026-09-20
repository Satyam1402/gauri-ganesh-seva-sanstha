<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MenuLinkType;
use App\Enums\MenuLocation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderMenuItemsRequest;
use App\Http\Requests\Admin\StoreMenuItemRequest;
use App\Http\Requests\Admin\UpdateMenuItemRequest;
use App\Interfaces\MenuItemRepositoryInterface;
use App\Models\MenuItem;
use App\Services\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    public function __construct(
        private MenuItemRepositoryInterface $items,
        private MenuService $menuService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', MenuItem::class);

        $location = MenuLocation::tryFrom((string) $request->query('location')) ?? MenuLocation::Header;

        return view('admin.menu-items.index', [
            'location' => $location,
            'locations' => MenuLocation::options(),
            'items' => $this->items->allForLocation($location),
        ] + $this->formData($location));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', MenuItem::class);

        $location = MenuLocation::tryFrom((string) $request->query('location')) ?? MenuLocation::Header;

        return view('admin.menu-items.create', ['location' => $location] + $this->formData($location));
    }

    public function store(StoreMenuItemRequest $request): RedirectResponse
    {
        $this->authorize('create', MenuItem::class);

        $item = $this->menuService->createItem($request->validated());

        return redirect()->route('admin.menu-items.index', ['location' => $item->location->value])
            ->with('status', 'Menu item added.');
    }

    public function edit(MenuItem $menuItem): View
    {
        $this->authorize('update', $menuItem);

        return view('admin.menu-items.edit', [
            'item' => $menuItem,
            'location' => $menuItem->location,
        ] + $this->formData($menuItem->location, $menuItem));
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): RedirectResponse
    {
        $this->authorize('update', $menuItem);

        $this->menuService->updateItem($menuItem, $request->validated());

        return redirect()->route('admin.menu-items.index', ['location' => $menuItem->location->value])
            ->with('status', 'Menu item updated.');
    }

    public function toggle(MenuItem $menuItem): RedirectResponse
    {
        $this->authorize('update', $menuItem);

        $this->menuService->toggleActive($menuItem);

        return back()->with('status', $menuItem->is_active ? 'Menu item enabled.' : 'Menu item disabled.');
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $this->authorize('delete', $menuItem);

        $location = $menuItem->location->value;
        $this->menuService->deleteItem($menuItem);

        return redirect()->route('admin.menu-items.index', ['location' => $location])
            ->with('status', 'Menu item deleted.');
    }

    public function reorder(ReorderMenuItemsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', MenuItem::class);

        $location = MenuLocation::tryFrom((string) $request->input('location')) ?? MenuLocation::Header;
        $this->menuService->reorder($location, $request->validated('order'));

        return response()->json(['status' => 'ok']);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(MenuLocation $location, ?MenuItem $editing = null): array
    {
        $parents = $location->supportsChildren()
            ? MenuItem::query()->forLocation($location)->topLevel()
                ->when($editing, fn ($q) => $q->whereKeyNot($editing->id))
                ->orderBy('order_column')->pluck('label', 'id')->all()
            : [];

        return [
            'linkTypes' => MenuLinkType::options(),
            'routes' => MenuItem::LINKABLE_ROUTES,
            'parents' => $parents,
        ];
    }
}
