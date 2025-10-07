<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TestPaper;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;


class TestPaperController extends Controller
{

    public function index()
    {
        try {

            $testpaper = TestPaper::orderBy('TestPaper_id', 'Asc')
                ->paginate(config('app.per_page'));
            return view('TestPaper.index', compact('testpaper'));
        } catch (\Throwable $th) {
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function add($id = null)
    {
        try {
            return view('TestPaper.add', compact('id'));
        } catch (\Throwable $th) {
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }
    public function store(Request $request)
    {
        $request->validate([
            'test_paper' => 'required|string',
            'language' => 'required',
        ]);

        TestPaper::create([
            'TestPaper_name' => $request->test_paper,
            'language' => $request->language,
            'strIP' => $request->ip(),

        ]);
        return redirect()->route('TestPaper.index')->with('success', 'Test Paper Added Successfully.');
    }

    public function edit(Request $request, $id)
    {

        try {
            $TestPaper = TestPaper::where('TestPaper_id', $id)->first();

            return view('TestPaper.edit', compact('TestPaper'));
        } catch (\Throwable $th) {

            DB::rollBack();

            return redirect()->back()->withInput()->with('error', $th->getMessage());
        }
    }

    public function update(Request $request)
    {

        DB::beginTransaction();

        try {


            $data = [


                'TestPaper_id' => $request->TestPaper_id,
                'TestPaper_name' => $request->TestPaper_name,
                'language' => $request->language,
                'updated_at' => now(),
                'strIP' => $request->ip(),
            ];

            TestPaper::where("TestPaper_id", "=", $request->TestPaper_id)->update($data);
            DB::commit();

            Toastr::success('Test Paper updated successfully :)', 'Success');
            return redirect()->route('TestPaper.index', $request->TestPaper_id)->with('success', 'Test Paper Update Successfully.');
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
            $TestPaper = TestPaper::where([

                'isDelete' => 0,
                'TestPaper_id' => $request->id
            ])->delete();

            DB::commit();

            Toastr::success('Test Paper delete successfully :)', 'Success');
            return response()->json(['success' => true]);
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

        try {
            $ids = $request->input('TestPaper_ids', []);
            TestPaper::whereIn('TestPaper_id', $ids)->delete();

            Toastr::success('Test Paper deleted successfully :)', 'Success');
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
}
