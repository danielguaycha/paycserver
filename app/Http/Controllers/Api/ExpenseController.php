<?php

namespace App\Http\Controllers\Api;

use App\Expense;
use App\Http\Controllers\ApiController;
use App\Traits\UploadTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExpenseController extends ApiController
{
    use UploadTrait;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $page = 1;
        $wheres = [
            ['status', Expense::STATUS_ACTIVO ],
            ['user_id', $request->user()->id]
        ];

        // page
        if ($request->query('page'))
            $page = $request->query('page');

        // date
        if($request->query('from') && !$request->query('to'))
            array_push($wheres, ['date', '=', $request->query('from')]);

        if($request->query('from') && $request->query('to')){
            array_push($wheres, ['date', '>=', $request->query('from')]);
            array_push($wheres, ['date', '<=', $request->query('to')]);
        }

        $limit = 20;
        $offset = ($page-1) * $limit;


        $expenses = Expense::select('id', 'monto', 'category', 'date')
            ->where($wheres)
            ->offset($offset)->limit($limit)
            ->orderBy('date', 'desc')->get();

        return $this->showAll($expenses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'monto' => 'required|numeric',
            'category' => 'required|string|max:50',
            'date' => 'nullable|date_format:Y-m-d',
            'description' => 'nullable|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg',
        ]);

        $expensive = new Expense();
        $expensive->monto = $request->get('monto');
        $expensive->category = Str::upper($request->get('category'));
        $expensive->description = $request->get('description');
        $expensive->user_id = $request->user()->id;

        if($request->get('date')){
            $expensive->date = $request->get('date');
        } else {
            $expensive->date = Carbon::now()->format('Y-m-d');
        }

        if($request->hasFile('image')){
            $expensive->image = $this->uploadOne($request->file('image'), '/expense', 'public');
        }

        if($expensive->save())
            return $this->showOne($expensive);
        else
            return $this->err('No se ha podido procesar el gasto, contacte con soporte');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
