<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SubCategories;
use App\Models\Categories;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;


class SubCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $subcategories = SubCategories::orderBy('strSubCategoryName', 'asc')->paginate(env('PER_PAGE'));
            // dd($subcategories);
            $categories = Categories::orderBy('Category_name', 'asc')
                ->select('Categories_id', 'Category_name')
                ->where(['iStatus' => 1])
                ->get();

            return view('sub_categories.index', compact('subcategories', 'categories'));
        } catch (\Throwable $th) {
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }



    public function store(Request $request)
    {
        DB::beginTransaction();


        try {
            // Validate input data
            $request->validate([
                'Categoryid' => 'required',
                'SubCategoryname' => 'required|unique:subcategory,strSubCategoryName',
                'SubCategoryicon' => [
                    'required',
                    'file',
                    'mimes:svg',

                ],
            ]);

            // Get the category name by Categoryid
            $category = Categories::where('Categories_id', $request->Categoryid)->first();

            // Check if category exists
            if (!$category) {
                Toastr::error('Category not found', 'Error');
                return redirect()->back()->withInput();
            }

            $categoriesname = $category->Category_name;  // Retrieve Category_name

            // Format the subcategory slug name
            $lowercase = Str::lower($request->SubCategoryname);
            $slugname = str_replace(' ', '-', $lowercase);

            // Handle image upload
            $img = "";
            if ($request->hasFile('SubCategoryimg')) {
                $image = $request->file('SubCategoryimg');
                $img = time() . '_' . date('dmYHis') . '.' . $image->getClientOriginalExtension();
                $destinationpath = public_path('/upload/subcategory-images/');

                // Create the directory if it doesn't exist
                if (!file_exists($destinationpath)) {
                    mkdir($destinationpath, 0755, true);
                }

                // Move the image to the destination
                $image->move($destinationpath, $img);
            }

            // Handle the Category icon upload
            if ($request->hasFile('SubCategoryicon')) {
                $iconFile = $request->file('SubCategoryicon');
                $iconExtension = $iconFile->getClientOriginalExtension();

                $icon = time() . '_' . date('dmYHis') . '.' . $iconExtension;
                $destinationPath = public_path('/upload/subcategory-icons/');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $iconFile->move($destinationPath, $icon);
            }
            // dd($icon);

            // Create a new SubCategory record
            SubCategories::create([
                'strCategoryName' => $categoriesname,
                'display_homepage' => $request->display_homepage,
                'strSubCategoryName' => $request->SubCategoryname,
                'iCategoryId' => $request->Categoryid,
                'strSlugName' => $slugname,
                'SubCategories_img' => $img,
                'subCategory_icon' => $icon,
                'created_at' => now(),
                'strIP' => $request->ip(),
            ]);

            // Commit the transaction
            DB::commit();

            Toastr::success('SubCategory created successfully!', 'Success');
            return redirect()->route('sub_categories.index')->with('success', 'SubCategory Created Successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();
            $errors = $e->errors();  // Get the validation errors
            $errorMessages = [];
            foreach ($errors as $field => $messages) {
                foreach ($messages as $message) {
                    $errorMessages[] = $message;
                }
            }

            $errorMessageString = implode(', ', $errorMessages);
            Toastr::error($errorMessageString, 'Error');
            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            DB::rollBack();
            Toastr::error('Failed to create SubCategory: ' . $th->getMessage(), 'Error');
            return redirect()->back()->withInput()->with('error', $th->getMessage());
        }
    }


    public function edit(Request $request)
    {

        $subcategory = SubCategories::where('iSubCategoryId', $request->id)->first();

        return json_encode($subcategory);
    }

    public function update(Request $request)
    {
        // dd($request);
        DB::beginTransaction();

        $category = Categories::where('Categories_id', $request->Categoryid)->first();
        $categoriesname = $category->Category_name;  // Retrieve Category_name


        try {
            $request->validate([
                'SubCategoryname' => 'required|unique:subcategory,strSubCategoryName,' . $request->iSubCategoryId . ',iSubCategoryId',
                'EditSubCategoryicon' => [
                    'file',
                    'mimes:svg',

                ],
            ]);

            $lowercase = Str::lower($request->SubCategoryname);

            $slugname = str_replace(' ', '-', $lowercase);


            $img = "";

            if ($request->hasFile('SubCategories_img')) {
                $image = $request->file('SubCategories_img');

                // Generate a unique file name
                $img = time() . '_' . date('dmYHis') . '.' . $image->getClientOriginalExtension();
                $destinationpath = public_path('upload/subcategory-images');

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
                // If no image is uploaded, keep the old image name
                $img = $request->input('hiddenPhoto');
            }

            // Handle Category Icon Upload
            $icon = $request->input('hiddensubicon'); // Default to the old icon
            if ($request->hasFile('EditSubCategoryicon')) {
                $iconFile = $request->file('EditSubCategoryicon');
                $iconExtension = $iconFile->getClientOriginalExtension();
                $mimeType = $iconFile->getMimeType();
                // Save the SVG file
                $icon = time() . '_' . date('dmYHis') . '.' . $iconExtension;
                $destinationPath = public_path('/upload/subcategory-icons/');
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



            // Update category with new image or old image
            $data = [
                'strCategoryName' => $categoriesname,
                'display_homepage' => $request->display_homepage,
                'strSubCategoryName' => $request->SubCategoryname,
                'SubCategories_img' => $img,
                'subCategory_icon' => $icon,
                'strSlugName' => $slugname,
                'updated_at' => now(),
                'strIP' => $request->ip(),
            ];

            SubCategories::where("iSubCategoryId", $request->iSubCategoryId)->update($data);

            DB::commit();

            Toastr::success('SubCategory updated successfully :)', 'Success');
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
            $state = SubCategories::where([

                'isDelete' => 0,
                'iSubCategoryId' => $request->id
            ])->delete();

            DB::commit();

            Toastr::success('SubCategory deleted successfully :)', 'Success');
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
            $ids = $request->input('iSubCategoryIds', []);
            SubCategories::whereIn('iSubCategoryId', $ids)->delete();

            Toastr::success('SubCategory deleted successfully :)', 'Success');
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
        // dd($request);
        try {
            if ($request->status == 1) {
                SubCategories::where(['iSubCategoryId' => $request->iSubCategoryId])->update(['iStatus' => 0]);
                Toastr::success('Subcategory inactive successfully :)', 'Success');
            } else {
                SubCategories::where(['iSubCategoryId' => $request->iSubCategoryId])->update(['iStatus' => 1]);
                Toastr::success('Subcategory active successfully :)', 'Success');
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
