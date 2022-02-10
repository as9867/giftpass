<?php

namespace App\Domains\Card\Http\Controllers\Backend;

use DB;
use App\Domains\Auth\Models\User;
use App\Http\Controllers\Controller;
use App\Domains\Auth\Models\WalletTransaction;
use App\Domains\Card\Models\Brand;
use App\Domains\Card\Models\Categories;
use App\Domains\Marketplace\Models\Bidding;
use App\Domains\Marketplace\Models\Marketplace;
use App\Domains\Marketplace\Models\OfferTrades;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function index()
    {
        return view('backend.brand.category_create');
    }

    // create new category

    public function create(Request $request)
    {
        $category = Categories::create($request->all());
        return redirect()->back()->withFlashSuccess('Category created succesfully.');
    }

    public function showCategory(Request $request)
    {
        return view('backend.brand.show_category');
    }

    public function editCategory(Categories $categories, Request $request)
    {
        return view('backend.brand.category_edit');
    }

    // create new brand

    public function addbrand(Request $request)
    {
        $cartegory = Categories::get();
        return view('backend.brand.brand_create', [
            'cartegory' => $cartegory
        ]);
    }

    public function createbrand(Request $request)
    {
        $image_name = null;
        if($request->file('logo')){
            $originalImage = $request->file('logo');
            $image_name = 'img/brand/'.$request->file('logo')->getClientOriginalName();
            $originalImage->move(public_path() . '/img/brand/', $img = $request->file('logo')->getClientOriginalName());
        }
        $data = [
            'name' => $request->name,
            'logo' => $image_name,
            'bg_color' => $request->bg_color,
            'terms_and_conditions' => $request->terms_and_conditions,
            'description' => $request->description,
            'how_to_redeem' => $request->how_to_redeem,
            'category_id' => $request->category_id
        ];
        Brand::create($data);
        return redirect()->back()->withFlashSuccess('Brand created succesfully.');
    }

    public function showBrand()
    {
        return view('backend.brand.show_brand');
    }

    public function editBrand(Brand $brand, Request $request)
    {
        $category = Categories::get();
        return view('backend.brand.brand_edit', [
            'category' => $category,
            'brand' => $brand
        ]);
    }

    public function updateBrand(Request $request)
    {
        if($request->file('logo')){
            $originalImage = $request->file('logo');
            $image_name = 'img/brand/'.$request->file('logo')->getClientOriginalName();
            $originalImage->move(public_path() . '/img/brand/', $img = $request->file('logo')->getClientOriginalName());
            $data = [
                'name' => $request->name,
                'logo' => $image_name,
                'bg_color' => $request->bg_color,
                'terms_and_conditions' => $request->terms_and_conditions,
                'description' => $request->description,
                'how_to_redeem' => $request->how_to_redeem,
                'category_id' => $request->category_id
            ];
        } else{
            $data = [
                'name' => $request->name,
                'bg_color' => $request->bg_color,
                'terms_and_conditions' => $request->terms_and_conditions,
                'description' => $request->description,
                'how_to_redeem' => $request->how_to_redeem,
                'category_id' => $request->category_id
            ];
        }

        $brand = Brand::where('id', $request->brand_id)->update($data);
        if ($brand) {
            $category = Categories::get();
            $brand = Brand::where('id', $request->brand_id)->first();
            return redirect()->route('admin.brand.editbrand', [
                'category' => $category,
                'brand' => $brand
            ]);
        }
    }

    public function delete(Brand $brand)
    {
        $is_update = $brand->update(['active' => 0]);
        if ($is_update) {
            return redirect()->route('admin.brand.showbrand');
        }
    }
}
