<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\MetaData;

class MetaDataController extends Controller
{
    public function index()
    {
        $data = MetaData::paginate(env('PER_PAGE'));
        return view('metaData.index', compact('data'));
    }

    public function edit($id)
    {
        $data = MetaData::whereId($id)->first();
        return view('metaData.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $updateData = array(
            'pagename' => $request->pagename,
            'metaTitle' => $request->metaTitle,
            'metaKeyword' => $request->metaKeyword,
            'metaDescription' => $request->metaDescription,
            'head' => $request->head,
            'body' => $request->body
        );
        MetaData::whereId($id)->update($updateData);
        $data = MetaData::whereId($id)->get();

        return redirect()->route('metaData.index')->with('success', 'Data Updated Successfully');
    }
}
