<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExamDetail;
use App\Models\Categories;
use App\Models\Question;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;


class QuestionController extends Controller
{

    public function hindiquestion()
    {
        try {

            $hindiques = Question::where('language', 1)->orderBy('test_question_id', 'desc')
                ->paginate(config('app.per_page'));

            return view('Question.hindiquestionlist', compact('hindiques'));
        } catch (\Throwable $th) {
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }
    public function gujaratiquestion()
    {
        try {

            $gujaratiques = Question::where('language', 3)->orderBy('test_question_id', 'desc')
                ->paginate(config('app.per_page'));

            return view('Question.gujaratiquestionlist', compact('gujaratiques'));
        } catch (\Throwable $th) {
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }
    public function englishquestion()
    {
        try {

            $englishques = Question::where('language', 2)->orderBy('test_question_id', 'desc')
                ->paginate(config('app.per_page'));

            return view('Question.englishquestionlist', compact('englishques'));
        } catch (\Throwable $th) {
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }
    public function questionlist($testpaperid, $lan)
    {
        try {

            $questionlist = Question::where('language', $lan)
                ->where('TestPaper_id', $testpaperid)
                ->orderBy('test_question_id', 'desc')
                ->paginate(config('app.per_page'));

            return view('Question.index', compact('questionlist', 'testpaperid', 'lan'));
        } catch (\Throwable $th) {
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }
    public function index()
    {
        try {

            $questionlist = Question::orderBy('test_question_id', 'desc')
                ->paginate(config('app.per_page'));
            //dd($questionlist);
            return view('Question.index', compact('questionlist'));
        } catch (\Throwable $th) {
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function add($id, $lan)
    {
        try {
            return view('Question.addquestion', compact('id', 'lan'));
        } catch (\Throwable $th) {
            Toastr::error('Error: ' . $th->getMessage());
            return redirect()->back()->withInput();
        }
    }
    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'correct_answer' => 'required',
        ]);
        $img = "";
        if ($request->hasFile('Image')) {
            $root = $_SERVER['DOCUMENT_ROOT'];
            $image = $request->file('Image');

            $img = time() . '_' . date('dmYHis') . '.' . $image->getClientOriginalExtension();
            $destinationpath = $root . '/driving_school/upload/questionImage/';

            // Create the directory if it doesn't exist
            if (!file_exists($destinationpath)) {
                mkdir($destinationpath, 0755, true);
            }

            // Move the image to the destination
            $image->move($destinationpath, $img);
        }

        Question::create([
            'question' => $request->question,
            'option_1' => $request->option_1,
            'option_2' => $request->option_2,
            'option_3' => $request->option_3,
            'answer' => $request->correct_answer,
            'language' => $request->language,
            'TestPaper_id' => $request->test_paper_id,
            'image' => $img,
            'strIP' => $request->ip(),

        ]);

        return redirect()->route('question.questionlist', [
            $request->test_paper_id,
            $request->language
        ])->with('success', 'Question added successfully!');
    }

    public function edit(Request $request, $id)
    {

        try {
            $exams = Question::where('test_question_id', $id)->first();

            return view('Question.edit', compact('exams'));
        } catch (\Throwable $th) {

            DB::rollBack();

            return redirect()->back()->withInput()->with('error', $th->getMessage());
        }
    }

    public function update(Request $request)
    {

        DB::beginTransaction();

        try {

            $img = "";

            if ($request->hasFile('editmain_img')) {
                $root = $_SERVER['DOCUMENT_ROOT'];
                $image = $request->file('editmain_img');

                $img = time() . '_' . date('dmYHis') . '.' . $image->getClientOriginalExtension();

                $destinationpath = $root . '/driving_school/upload/questionImage/';

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


            $data = [

                'test_question_id' => $request->test_question_id,
                'question' => $request->question,
                'option_1' => $request->option_1,
                'option_2' => $request->option_2,
                'option_3' => $request->option_3,
                'image' =>  $img,
                'answer' => $request->correct_answer,
                'updated_at' => now(),
                'strIP' => $request->ip(),
            ];

            Question::where("test_question_id", "=", $request->test_question_id)->update($data);
            DB::commit();

            Toastr::success('Question Answer updated successfully :)', 'Success');
            return redirect()->route('question.questionlist', [
                $request->TestPaper_id,
                $request->language
            ])->with('success', 'Question Answer Update Successfully.');
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
            $exam = Question::where([

                'isDelete' => 0,
                'test_question_id' => $request->id
            ])->delete();

            DB::commit();

            Toastr::success('Question delete successfully :)', 'Success');
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
            $ids = $request->input('test_question_ids', []);
            Question::whereIn('test_question_id', $ids)->delete();

            Toastr::success('Question deleted successfully :)', 'Success');
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
