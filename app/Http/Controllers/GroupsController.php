<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Group;
use App\Question;
use Carbon\Carbon;
use App\User;
use App\Answer;
use Illuminate\Support\Facades\DB;
class GroupsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $current_user = Auth::user();
        $userGroups = $current_user->groups;
        return view('group.index',['userGroups' => $userGroups]);
    }
    public function getGroups()
    {
      $current_user = Auth::user();
      $userGroups = $current_user->groups;
      return response()->json(['groups' => $userGroups],200);

    }
    public function addUser($idUser, $idGroup)
    {

      $group = Group::find($idGroup);

      $belongsToGroup = DB::table('group_user')->where([
                                                  ['user_id','=', $idUser],
                                                  ['group_id','=', $idGroup],
                                                ])->first();
      if ($belongsToGroup != null) {
        return response()->json(['response' => false],200);
      }

      $group->users()->attach($idUser);
      return response()->json(['response' => true],200);
    }
    public function deleteUser($idUser, $idGroup)
    {

      $group = Group::find($idGroup);

      $group->users()->detach($idUser);
      return response()->json(['response' => true],200);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('group.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $group = new Group();
        $group->name = $request->name;
        $group->user_id = Auth::user()->id;
        $group->save();
        $question = Question::create([
          'title' => '¿Qué te parece este grupo?',
          'description' => 'pregunta',
          'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
          'user_id' => Auth::user()->id,
          'group_id' => $group->id,
          'state' => 'propuesta',
        ]);
        Answer::create([
          'description' => 'Normal',
          'question_id' => $question->id,
        ]);
        Answer::create([
          'description' => 'Bueno',
          'question_id' => $question->id,
        ]);
        Answer::create([
          'description' => 'Muy bueno',
          'question_id' => $question->id,
        ]);

        return response()->json(['group' => $group],200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $group = Group::find($id);
        $owner = User::find($group->user_id);
        $current_date = Carbon::now();
        $sundayOfLastWeek = Carbon::now()->previous(Carbon::SUNDAY)->format('Y-m-d H:i:s');
        if ($current_date->dayOfWeek == 1) {
            $mondayOfLastWeek = Carbon::now()->previous(Carbon::MONDAY)->format('Y-m-d H:i:s');

        }else{
          $mondayOfLastWeek = Carbon::now()->previous(Carbon::MONDAY)->previous(Carbon::MONDAY)->format('Y-m-d H:i:s');
        }

        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d H:i:s');
        $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d H:i:s');

        $theWinner = Question::where([

                                      ['created_at','>=',$mondayOfLastWeek],
                                      ['created_at','<=',$sundayOfLastWeek],
                                      ['group_id','=',$id]
                                    ])
                              ->orderBy('votes','DESC')->first();
        if ($theWinner->state == "propuesta") {
          $theWinner->state = "ganadora";
          $theWinner->save();
        }

        $questions = Question::where([

                                      ['created_at','>=',$startOfWeek],
                                      ['created_at','<=',$endOfWeek],
                                      ['group_id', '=', $id]
                                    ])
                              ->orderBy('votes','DESC')->get();
        return view('group.show',['group' => $group, 'questions' => $questions, 'questionWeek' => $theWinner, 'owner' => $owner]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $group = Group::find($id);
        return view('question.update',['group' => $group]);
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
        $group = Group::find($id);
        $group->name = $request->name;
        $group->save();
        return redirect()->route('groups.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $group = Group::find($id);
        $group->delete();
        return redirect()->route('groups.index');
    }
}
