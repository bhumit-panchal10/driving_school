<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Categories;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;


class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {

            $categories = Categories::orderBy('Category_name', 'asc')->paginate(env('PER_PAGE'));

            // dd($states);

            return view('categories.index', compact('categories'));
        } catch (\Throwable $th) {
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction(); // Start a database transaction

        try {
            $request->validate([
                'Categoryname' => 'required|unique:Categories,Category_name',
                'Categoryicon' => [
                    'required',
                    'file',
                    'mimes:svg',

                ],
            ]);


            $lowercase = Str::lower($request->Categoryname);
            $slugname = str_replace(' ', '-', $lowercase);

            $img = "";
            $icon = "";

            // Handle the Category image upload
            if ($request->hasFile('Categoryimg')) {
                $root = $_SERVER['DOCUMENT_ROOT'];
                $image = $request->file('Categoryimg');
                $img = time() . '_' . date('dmYHis') . '.' . $image->getClientOriginalExtension();
                $destinationpath = $root . '/upload/category-images/';

                if (!file_exists($destinationpath)) {
                    mkdir($destinationpath, 0755, true);
                }

                $image->move($destinationpath, $img);
            }

            // Handle the Category icon upload
            if ($request->hasFile('Categoryicon')) {
                $iconFile = $request->file('Categoryicon');
                $iconExtension = $iconFile->getClientOriginalExtension();

                $icon = time() . '_' . date('dmYHis') . '.' . $iconExtension;
                $destinationPath = public_path('/upload/category-icons/');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $iconFile->move($destinationPath, $icon);
            }

            // Create a new Categories record
            $Categories = Categories::create([
                'Category_name' => $request->Categoryname,
                'display_homepage' => $request->display_homepage,
                'Categories_slug' => $slugname,
                'Categories_img' => $img,
                'Categories_icon' => $icon,
                'created_at' => now(),
                'strIP' => $request->ip(),
            ]);

            DB::commit(); // Commit the transaction

            Toastr::success('Categories created successfully :)', 'Success');
            return redirect()->route('categories.index')->with('success', 'Categories Created Successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();
            $errors = $e->errors();
            $errorMessageString = implode(', ', Arr::flatten($errors));

            Toastr::error($errorMessageString, 'Error');
            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            DB::rollBack();
            Toastr::error('Failed to create Categories: ' . $th->getMessage(), 'Error');
            return redirect()->back()->withInput()->with('error', $th->getMessage());
        }
    }



    public function edit(Request $request)
    {

        $category = Categories::where('Categories_id', $request->id)->first();
        return json_encode($category);
    }

    public function update(Request $request)
    {

        DB::beginTransaction();

        try {
            $request->validate([
                'Category_name' => 'required|unique:Categories,Category_name,' . $request->Categoriesid . ',Categories_id',
                'Categoryicon' => [
                    'file',
                    'mimes:svg',

                ],
            ]);

            $lowercase = Str::lower($request->Category_name);
            $slugname = str_replace(' ', '-', $lowercase);

            $img = "";
            $icon = "";

            // Handle Category Image Upload
            if ($request->hasFile('Categoryimg')) {
                $image = $request->file('Categoryimg');

                // Generate a unique file name
                $img = time() . '_' . date('dmYHis') . '.' . $image->getClientOriginalExtension();
                $destinationpath = public_path('upload/category-images');

                // Create directory if it doesn't exist
                if (!file_exists($destinationpath)) {
                    mkdir($destinationpath, 0755, true);
                }

                // Move the image to the destination path
                $image->move($destinationpath, $img);

                // Delete the old image if it exists
                $oldImg = $request->input('hiddenPhoto');
                if ($oldImg && file_exists($destinationpath . '/' . $oldImg)) {
                    unlink($destinationpath . '/' . $oldImg);
                }
            } else {
                $img = $request->input('hiddenPhoto'); // Retain the old image if no new image is uploaded
            }

            // Handle Category Icon Upload
            $icon = $request->input('hiddeniconPhoto'); // Default to the old icon
            if ($request->hasFile('Categoryicon')) {
                $iconFile = $request->file('Categoryicon');
                $iconExtension = $iconFile->getClientOriginalExtension();
                $mimeType = $iconFile->getMimeType();
                // Save the SVG file
                $icon = time() . '_' . date('dmYHis') . '.' . $iconExtension;
                $destinationPath = public_path('/upload/category-icons/');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $iconFile->move($destinationPath, $icon);

                // Delete the old icon if it exists
                $oldIcon = $request->input('hiddenIcon');
                if ($oldIcon && file_exists($destinationPath . '/' . $oldIcon)) {
                    unlink($destinationPath . '/' . $oldIcon);
                }
            }

            // Update the category record
            $data = [
                'Category_name' => $request->Category_name,
                'display_homepage' => $request->display_homepage,
                'Categories_img' => $img,
                'Categories_icon' => $icon,
                'Categories_slug' => $slugname,
                'updated_at' => now(),
                'strIP' => $request->ip(),
            ];

            Categories::where("Categories_id", $request->Categoriesid)->update($data);

            DB::commit();

            Toastr::success('Category updated successfully :)', 'Success');
            return back();
        } catch (ValidationException $e) {
            DB::rollBack();
            $errorMessageString = implode(', ', Arr::flatten($e->errors()));
            Toastr::error($errorMessageString, 'Error');
            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            DB::rollBack();
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }







    public function delete(Request $request)
    {

        DB::beginTransaction();

        try {
            $state = Categories::where([

                'isDelete' => 0,
                'Categories_id' => $request->id
            ])->delete();

            DB::commit();

            Toastr::success('Category deleted successfully :)', 'Success');
            return response()->json(['success' => true]);
            //return back();
        } catch (ValidationException $e) {
            DB::rollBack();
            Toastr::error('Validation Error: ' . implode(', ', $e->errors()));
            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            DB::rollBack();
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function deleteselected(Request $request)
    {
        // dd($request->all());
        try {
            $ids = $request->input('Categories_ids', []);
            Categories::whereIn('Categories_id', $ids)->delete();

            Toastr::success('Category deleted successfully :)', 'Success');
            return back();
        } catch (ValidationException $e) {
            DB::rollBack();
            Toastr::error('Validation Error: ' . implode(', ', $e->errors()));
            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            DB::rollBack();
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function updateStatus(Request $request)
    {


        try {
            if ($request->status == 1) {
                Categories::where(['Categories_id' => $request->categoryId])->update(['iStatus' => 0]);
                Toastr::success('Category inactive successfully :)', 'Success');
            } else {
                Categories::where(['Categories_id' => $request->categoryId])->update(['iStatus' => 1]);
                Toastr::success('Category active successfully :)', 'Success');
            }
            echo 1;
        } catch (ValidationException $e) {
            DB::rollBack();
            Toastr::error('Validation Error: ' . implode(', ', $e->errors()));
            return 0;
        } catch (\Throwable $th) {
            DB::rollBack();
            Toastr::error('Error: ' . $th->getMessage());
            return 0;
        }
    }
}
