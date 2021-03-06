<?php

namespace App\Http\Controllers;
use App\Question;
use App\Answer;
use App\Group;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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
                                      ['created_at','<=',$sundayOfLastWeek]
                                    ])
                              ->orderBy('votes','DESC')->first();
        if ($theWinner->state == "propuesta") {
          $theWinner->state = "ganadora";
          $theWinner->save();
        }

        $questions = Question::where([

                                      ['created_at','>=',$startOfWeek],
                                      ['created_at','<=',$endOfWeek]
                                    ])
                              ->orderBy('votes','DESC')->get();
        return view('question.index',['questions' => $questions, 'theWinner' => $theWinner]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('question.create');
    }
    public function winners($id)
    {
      $group = Group::find($id);

      $questions = $group->questions->where('state','ganadora');
      return view('question.winners', ['questions' => $questions]);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $current_user = Auth::user();
        $question = new Question();
        $question->title = $request->title;
        $question->description = "pregunta de la semana";
        $question->state = 'propuesta';
        $question->votes = 0;
        $question->public = $request->public;
        $question->group_id = $request->group_id;
        $question->user_id = $current_user->id;
        $question->save();
        //first option
        $answer1 = new Answer();
        $answer1->description = $request->answer1;
        $answer1->question_id = $question->id;
        $answer1->save();
        //second option
        $answer2 = new Answer();
        $answer2->description = $request->answer2;
        $answer2->question_id = $question->id;
        $answer2->save();
        //third option
        $answer3 = new Answer();
        $answer3->description = $request->answer3;
        $answer3->question_id = $question->id;
        $answer3->save();
        $question->answers;
        $question->alreadyVote = false;
        $question->user;

        return response()->json(['question' => $question],200);
        //return redirect()->route('questions.index');
    }
    //find question, add vote to question and disable question to user()
    public function addVote(Request $request, $id){

        $question = Question::find($id);
        $current_user = Auth::user();
        $question->votes += 1;
        $question->users()->attach($current_user);
        $question->save();
        return response()->json(['question' => $question],200);
    }
    public function registerVote(Rquest $request, $id)
    {


    }
    public function totalVotes($id)
    {
      $question = Question::find($id);
      $answers = $question->answers;
      return response()->json(['answers'=>$answers]);
    }
    public function showvotes($id)
    {
      $question = Question::find($id);
      return view('question.votes',['question'=>$question]);
    }
    public function graphics()
    {
      return view('question.graphics');
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $question = Question::findOrFail($id);
        $answers = $question->answers;
        return view('question.show',['question'=>$question, 'answers'=>$answers]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $question = Question::findOrFail($id);
        return view('question.show',['question'=>$question]);
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
        $question = Question::findOrFail($id);
        $question->delete();

        return redirect()->route('questions.index');
    }
}
