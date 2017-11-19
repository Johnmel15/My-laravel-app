<?php

namespace App\Http\Controllers;

use App\Admin;
use App\AlumniList;
use App\AlumniTracer;
use App\GraduatePerCourse;
use App\OrgFileTable;
use App\OrgUser;
use App\Posts;
use App\Program;
use App\SchoolYear;
use App\StaffFileTable;
use App\StaffUser;
use App\StudPregnantTracer;
use App\StudVioTracer;
use App\TableMinutes;
use App\User;
use App\ViolationList;
use App\studMasterList;
use Auth;
use Carbon\Carbon;
use Charts;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Imported DB facade
use Illuminate\Support\Facades\Input;
use Mail;
use Redirect;
use Response;
use Session;
use Validator;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $count = DB::table('alumni_tracers')
        //          ->select('name', DB::raw('count(*) as total'))
        //          ->groupBy('name')
        //          ->get();
        $count = AlumniList::select('id', DB::raw("count(id)"))->groupBy('id')->get(); // Import DB facade
        $count1 = StudVioTracer::select('name', \DB::raw("count(name)"))->groupBy('name')->get();
        $count2 = StudPregnantTracer::count();
        $count3 = OrgFileTable::count();
        $count4 = StaffFileTable::count();
        $count5 = AlumniTracer::select('id', \DB::raw("count(id)"))->groupBy('id')->get();
        $posts = Posts::orderBy('id', 'desc')->paginate(5);

        return view('Admin/admin')->with(compact('count','count1', 'count2', 'posts', 'count3', 'count4', 'count5'));
    }

    public function upProfile()
    {
       // $alum = User::find($id);
       // return view('update')->with(compact('alum'));
        return view('Admin/upProfile'); // better use small letters on your views naming convension.
    }

    // You may try to extract this method into more SOLID approach.
    // Try to watch and understand Single approach on Laracasts.com
    // https://laracasts.com/series/solid-principles-in-php/episodes/1
    // or research about SOLID.
    public function viewUpProfile(Request $request, $id)
    {
        // $this->validate($request, array(
        //     'name' => 'required',
        //     'email' => 'required|email',
        //     'password' => 'required|min:6',
        // ));

        $user = Admin::findOrFail($id);

        if (Hash::check($request->get('c_password'), $user['password'])) {
            $user->name = $request->input('name');
            $user->username = $request->input('username');
            $user->password = Hash::make($request->password);

            $user->save();

            return redirect('/profile'); // use redirect(route('profile_route_name'))->withSuccess('Your account information has been change');
        } else if ($user->name != $request->input('name') or $user->username != $request->input('username')) {
            $user->name = $request->input('name');
            $user->username = $request->input('username');
            $user->save();

            // Session::flash('success', 'Your account information has been change');
            return redirect('/profile'); // use redirect(route('profile_route_name'))->withSuccess('Your account information has been change');
        } else if ($this->checkIfNoChangesWereMade($request, $user)) {
            // Extracted condition into method instead.
            return redirect('/profile'); // use redirect(route('profile_route_name'))->withSuccess('No changes were made.');
        } else {
            return redirect('/upProfile'); // use redirect(route('profile_route_name'))->withSuccess('Incorrect password.');
        }
    }

    public function profile()
    {
        return view('Admin/profile');
    }

    protected function checkIfNoChangesWereMade($request, $user)
    {
        return $user->name == $request->input('name') and $user->username == $request->input('username') and $request->get('c_password') == "" and $request->get('password') == "";
    }

    public function conPanel()
    {
        $progs = Program::all();
        $grads = GraduatePerCourse::all();
        $alums = AlumniList::all();
        $orgs = OrgUser::all();
        $offs = StaffUser::all();
        $studLists = studMasterList::all();
        $admin = Admin::all();
        $sy = SchoolYear::all();
        // $programs = DB::table('programs')
        //    ->orderBy('abbreviation', 'asc')
        //    ->get();
        // I assumed Program Model is using table programs, instead of always using DB, use the model class instead.
        $programs = Program::orderBy('abbreviation')->get(); // or create a scope like Program::byAbbreviation();

        return view('Admin/conpanel') // highly suggest to use small letters for view naming conventions.
               ->with(compact('progs', 'grads', 'alums', 'orgs', 'offs', 'programs', 'studLists', 'admin', 'sy'));
    }

    public function addAlumni(Request $request)
    {
        $this->validate($request, array(
            'firstname' => 'required',
            'lastname' => 'required',
            'batch' => 'required',
            'course' => 'required',
        ));
        // I personally use, Form Request Validation for SOLID approach.
        // Please check https://laravel.com/docs/5.5/validation#form-request-validation

        // $alum = new AlumniList;
        // $alum->firstname = $request->firstname;
        // $alum->lastname = $request->lastname;
        // $alum->batch = $request->batch;
        // $alum->course = $request->course;
        // $alum->save();
        // You can still use the above approach but I personally use the create method.
        // Since all request properties are just the same with the AlumniList columns and/or fillables,
        // Use the request `all()` method instead.

        $alum = AlumniList::create($request->all());

        return redirect('/conPanel');  // use redirect(route('profile_route_name'))->withSuccess('Saved!');
    }

    public function addCourse(Request $request)
    {
         $this->validate($request, array(
            'program' => 'required',
            'abbreviation' => 'required',
        ));
        // I personally use, Form Request Validation for SOLID approach.
        // Please check https://laravel.com/docs/5.5/validation#form-request-validation

        // $prog = new Program;
        // $prog->program = $request->program;
        // $prog->abbreviation = $request->abbreviation;
        // $prog->save();
        // Since all request properties are just the same with the AlumniList columns and/or fillables,
        // Use the request `all()` method instead.

        $prog = Program::create($request->all());

        return redirect('/conPanel'); // use redirect(route())->withSuccess('Saved!');
    }

    public function addGrad(Request $request)
    {
        $this->validate($request, array(
            'program' => 'required',
            'male' => 'required',
            'female' => 'required',
            'year' => 'required',
            'date_graduated' => 'required',
        ));
        // Again.

        $grad = new GraduatePerCourse;
        $grad->program = $request->program;
        $grad->year = $request->year;
        $grad->date_graduated = $request->date_graduated;
        $grad->male = $request->male;
        $grad->female = $request->female;
        $grad->total = ($request->female)+($request->male);
        $grad->save();
        // You may extend or add another method for your Request class for adding GraduatePerCourse like
        // $grad = GraduatePerCourse::create($request->allData());

        return redirect('/conPanel');  // use redirect(route())->withSuccess('Saved!');
    }

    public function addOrgAcc(Request $request)
    {
        $this->validate($request, array(
            'name' => 'required',
            'username' => 'required',
            'email' => 'required',
            'password' => 'required',
            'org_name' => 'required',
        ));
        // Again.

        $org = new OrgUser;
        $org->name = $request->name;
        $org->username = $request->username;
        $org->email = $request->email;
        $org->password = Hash::make($request->password);
        $org->org_name = $request->org_name;
        $org->save();
        // Again.

        return redirect('/conPanel'); // use redirect(route())->withSuccess('Saved!');
    }

    public function addStaffAcc(Request $request)
    {
        $this->validate($request, array(
            'name' => 'required',
            'username' => 'required',
            'office_name' => 'required',
        ));
        // Again.

        $name = $request->get('name'); // Limit your usage of Input Facades
        $email = $request->get('email');
        $username = $request->get('username');
        $office_name = $request->get('office_name');
        $pass = str_random(8);

        $data = [ 'name' => $name, 'pass' => $pass, 'username' => $username ]; // Use spaces the're free to use.
        Mail::send(['html' => 'mail.staffMessage'], $data, function($message) use ($email, $name, $username, $office_name, $pass) {
            $message->to($email)->subject('Thank you for signing in to KCAST-MIS');
            $message->from('johnmelviesulla.pixelprintojt@gmail.com', 'KCAST Administrator');
        });
        // You may use an Event here, like:
        // ```
        // event(new SendMailToStaffEvent($data, ....))
        // ```
        // Kindly read and understand https://laravel.com/docs/5.5/events#dispatching-events

        $org = new StaffUser;
        $org->name = $name;
        $org->username = $username;
        $org->email = $email;
        $org->password = Hash::make($pass); // Use bcrypt() or mutator method [https://laravel.com/docs/5.5/eloquent-mutators#accessors-and-mutators]
        $org->office_name = $office_name;
        $org->save();

        return redirect('/conPanel'); // use redirect(route())->withSuccess('Email was sent succesfully! Please check your email.');
    }

    public function alumniTracer()
    {
        $alumnus = DB::table('alumni_tracers')
                   ->select(DB::raw('firstname as firstname, lastname as lastname, status as status, COUNT(*) count'))
                   ->groupBy('firstname','lastname')
                   ->orderByRaw('min(created_at) desc')
                   ->get();

        $programs = DB::table('programs') // Again, and use single quote for consistency.
               ->orderBy('abbreviation', 'asc')
               ->get();

        $progs = GraduatePerCourse::all();
        $course = Program::all();
        $counts = DB::table('alumni_tracers')
            ->select(DB::raw('year_graduated as year_graduated, course as course, firstname as firstname, lastname as lastname'))
            ->groupBy('firstname', 'lastname')
            ->get();

        $prev_name = "";
        $prev_prog = 0;
        $year_graduated_name = "";
        $prev_totalprog = 0;
        $total_male = 0;
        $total_female = 0;
        $count=0;

        $male = AlumniTracer::select('id', \DB::raw("count(*)"))
        ->where('gender', '=', 'Male')
        ->groupBy('lastname', 'firstname')
        ->get();

        $female = AlumniTracer::select('id', \DB::raw("count(*)"))
        ->where('gender', '=', 'Female')
        ->groupBy('lastname', 'firstname')
        ->get();

        $gradCount = AlumniTracer::select('id', \DB::raw("count(*)"))
        ->groupBy('lastname', 'firstname')
        ->get();

        $salaryRate = DB::table('alumni_tracers')
              ->select(DB::raw("count(*) as count"), 'grad.*')
              ->join('graduate_per_courses as grad', 'grad.program', '=', 'alumni_tracers.course')
              ->leftjoin('graduate_per_courses as gradd', 'gradd.year', '=', 'alumni_tracers.year_graduated')
              ->groupBy('program')
              ->get();

        // $salaryRate = DB::table('graduate_per_courses')
        //     ->join('alumni_tracers as alums', 'graduate_per_courses.program', '=', 'alums.course')
        //     ->leftJoin('alumni_tracers as alum', 'graduate_per_courses.year', '=', 'alum.year_graduated')
        //     ->select('graduate_per_courses.program as program', 'graduate_per_courses.year as year', 'graduate_per_courses.total as total', DB::raw("count(alums.lastname) as count"))
        //     ->groupBy('alums.firstname', 'alums.lastname')
        //     ->get();



        return view('Admin/alumnitracer')->with(compact('alumnus', 'progs', 'counts', 'chart', 'prev_name', 'prev_prog', 'course', 'year_graduated_name', 'programs', 'samples', 'total_male', 'total_female', 'prev_totalprog', 'female', 'male', 'salaryRate', 'gradCount'));
    }

    public function alumniSingle($firstname, $lastname)
    {
        $alums = DB::table('alumni_tracers')
            ->where('firstname', 'like', '%' . $firstname . '%')
            ->where('lastname', 'like', '%' . $lastname . '%')
            ->orderby('created_at', 'desc')
            ->get();

        return view('Admin/alumniSingle')->with(compact('alums'));
    }

    public function fullDetails($id)
    {
        $alums = AlumniTracer::find($id);
        return view('Admin/fullDetails')->with(compact('alums'));
    }

    public function svctracer()
    {
        $sy = "2017-2018";

         // $chart = Charts::database(DB::table('stud_vio_tracers')
         //           ->select(DB::raw('name as name, program as program, violation as violation, COUNT(*) cases'))
         //           ->where("semester", '=', "1st")
         //           ->where("school_yr", 'like','%'.$sy.'%')
         //           ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
         //      ->title("Number of Cases Per Course")
         //      ->elementLabel("Total")
         //      ->responsive(false)
         //      ->height(400)
         //      ->width(900)
         //      ->groupBy('program');

         $chart = Charts::multiDatabase('bar', 'highcharts')
                ->colors(['gray','yellow', 'green', 'red', 'orange', '#34aadc', '#0088cc', 'pink'])
                ->dataset('BSIT', StudVioTracer::where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BSIT")
                   ->groupBy('name', 'program')->get())
                ->dataset('BSBA-Fin. Mgt.', StudVioTracer::where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BSBA-Fin. Mgt.")
                   ->groupBy('name', 'program')->get())
                ->dataset('BAT', StudVioTracer::where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BAT")
                   ->groupBy('name', 'program')->get())
                ->dataset('BSC', StudVioTracer::where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BSC")
                   ->groupBy('name', 'program')->get())
                ->dataset('BPA', StudVioTracer::where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BPA")
                   ->groupBy('name', 'program')->get())
                ->dataset('BEED-Generalist', StudVioTracer::where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BEED-Generalist")
                   ->groupBy('name', 'program')->get())
                ->dataset('BSED-English', StudVioTracer::where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BSED-English")
                   ->groupBy('name', 'program')->get())

                ->dataset('BSOA', StudVioTracer::where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BSOA")
                   ->groupBy('name', 'program')->get())

                ->title("Number of Cases Per Course")
                ->responsive(false)
                ->height(400)
                ->width(900)
                ->groupBy('');

         // $chartt = Charts::database(DB::table('stud_vio_tracers')
         //           ->select(DB::raw('name as name, program as program, violation as violation, COUNT(*) cases'))
         //           ->where("semester", '=', "2nd")
         //           ->where("school_yr", 'like','%'.$sy.'%')
         //           ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
         //      ->title("Number of Cases Per Course")
         //      ->elementLabel("Total")
         //      ->responsive(false)
         //      ->height(400)
         //      ->width(900)
         //      ->groupBy('program');

         $chartt = Charts::multiDatabase('bar', 'highcharts')
                ->colors(['gray','yellow', 'green', 'red', 'orange', '#34aadc', '#0088cc', 'pink'])
                ->dataset('BSIT', StudVioTracer::where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BSIT")
                   ->groupBy('name', 'program')->get())
                ->dataset('BSBA-Fin. Mgt.', StudVioTracer::where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BSBA-Fin. Mgt.")
                   ->groupBy('name', 'program')->get())
                ->dataset('BAT', StudVioTracer::where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BAT")
                   ->groupBy('name', 'program')->get())
                ->dataset('BSC', StudVioTracer::where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BSC")
                   ->groupBy('name', 'program')->get())
                ->dataset('BPA', StudVioTracer::where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BPA")
                   ->groupBy('name', 'program')->get())
                ->dataset('BEED-Generalist', StudVioTracer::where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BEED-Generalist")
                   ->groupBy('name', 'program')->get())
                ->dataset('BSED-English', StudVioTracer::where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BSED-English")
                   ->groupBy('name', 'program')->get())
                ->dataset('BSOA', StudVioTracer::where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->where("program", '=', "BSOA")
                   ->groupBy('name', 'program')->get())
                ->title("Number of Cases Per Course")
                ->responsive(false)
                ->height(400)
                ->width(900)
                ->groupBy('program');

        $chart1 = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('name as name, program as program, violation as violation, COUNT(*) cases'))
                   ->where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases Per Violation")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('violation');

        $chart11 = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('name as name, program as program, violation as violation, COUNT(*) cases'))
                   ->where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases Per Violation")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('violation');

        $chart2 = Charts::database(StudVioTracer::all(), 'bar', 'highcharts')
              ->title("Number of Cases Per Year")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupByYear(10);

        $chart3 = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('gender as gender, COUNT(*) cases'))
                   ->where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases By Gender")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('gender');

        $chart33 = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('gender as gender, COUNT(*) cases'))
                   ->where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases By Gender")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('gender');

        $studs = DB::table('stud_vio_tracers')
                   ->select(DB::raw('name as name, program as program, violation as violation, COUNT(violation) cases'))
                   ->groupBy('name', 'program')
                   ->orderByRaw('min(created_at) desc')
                   ->get();

        $counts = DB::table('stud_vio_tracers')
            ->select(DB::raw('name as name, program as program, violation as violation'))
            ->groupBy('name')
            ->get();

        $course = Program::all();
        $casess = 0;
        $prev_name = "";

        $violations = DB::table("violation_lists")
                   ->orderBy('violation', 'asc')
                   ->get();

        $programs = DB::table("programs")
                   ->orderBy('abbreviation', 'asc')
                   ->get();

        return view('Admin/svctracer')->with(compact('studs', 'violations', 'chart', 'chart1', 'chart2', 'chart3', 'chartt', 'chart11', 'chart22', 'chart33', 'programs', 'counts', 'sy'));
    }

    public function svcTracerr(Request $request)
    {
        $sy = $request->get('year');

         $chart = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('name as name, program as program, violation as violation, COUNT(*) cases'))
                   ->where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases Per Course")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('program');

         $chartt = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('name as name, program as program, violation as violation, COUNT(*) cases'))
                   ->where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases Per Course")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('program');

        $chart1 = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('name as name, program as program, violation as violation, COUNT(*) cases'))
                   ->where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases Per Violation")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('violation');

        $chart11 = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('name as name, program as program, violation as violation, COUNT(*) cases'))
                   ->where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases Per Violation")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('violation');

        $chart2 = Charts::database(StudVioTracer::all(), 'bar', 'highcharts')
              ->title("Number of Cases Per Year")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupByYear(10);

        $chart3 = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('gender as gender, COUNT(*) cases'))
                   ->where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases By Gender")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('gender');

        $chart33 = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('gender as gender, COUNT(*) cases'))
                   ->where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases By Gender")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('gender');


        $studs = DB::table('stud_vio_tracers')
                   ->select(DB::raw('name as name, program as program, violation as violation, COUNT(violation) cases'))
                   ->groupBy('name', 'program')
                   ->orderByRaw('min(created_at) desc')
                   ->get();

        $counts = DB::table('stud_vio_tracers')
            ->select(DB::raw('name as name, program as program, violation as violation'))
            ->groupBy('name')
            ->get();

        $course = Program::all();
        $casess = 0;
        $prev_name = "";

        $violations = DB::table("violation_lists")
                   ->orderBy('violation', 'asc')
                   ->get();

        $programs = DB::table("programs")
                   ->orderBy('abbreviation', 'asc')
                   ->get();

        return view('Admin/svctracer')->with(compact('studs', 'violations', 'chart', 'chart1', 'chart2', 'chart3', 'chartt', 'chart11', 'chart22', 'chart33', 'programs', 'counts', 'sy'));
    }

    public function svcForm()
    {
        $violations = DB::table('violation_lists')
               ->orderBy('violation', 'asc')
               ->get();

        $programs = DB::table('programs') // Again
               ->orderBy('abbreviation', 'asc')
               ->get();

        return view('Admin/svcForm')->with(compact('violations', 'programs'));
    }

    public function svcStore(Request $request)
    {
        $rules = array(
            'fname' => 'required',
            'lname' => 'required',
            'program' => 'required',
            'school_yr' => 'required',
            'semester' => 'required',
            'action_taken' => 'required',
            'minutes' => 'required',
            'remarks' => 'required',
            'violation' => 'required',
        );

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails())
        {
            $messages = $validator->messages();


            return Redirect::to('/svcForm')->withInput()->withErrors($validator); // you can use redirect() or back() helper instead of Redirect::to()
        } else {
            $other = $request->get('other_case');

            if ($other == "") {
                $stud = new StudVioTracer;
                $stud->name = $request->fname." ".$request->lname;
                $stud->program = $request->program;
                $stud->gender = $request->gender;
                $stud->date = $request->date;
                $stud->violation = $request->violation;
                $stud->school_yr = $request->school_yr;
                $stud->semester = $request->semester;
                $stud->action_taken = $request->action_taken;
                $stud->minutes = $request->minutes;
                $stud->remarks = $request->remarks;

                $min = new TableMinutes;
                $min->name = $request->fname." ".$request->lname;
                $min->program = $request->program;
                $min->gender = $request->gender;
                $min->date = $request->date;
                $min->violation = $request->violation;
                $min->school_yr = $request->school_yr;
                $min->semester = $request->semester;
                $min->action_taken = $request->action_taken;
                $min->minutes = $request->minutes;
                $min->remarks = $request->remarks;
                $stud->save();
                $min->save();
            } else {
                $stud = new StudVioTracer;
                $stud->name = $request->fname." ".$request->lname;
                $stud->program = $request->program;
                $stud->gender = $request->gender;
                $stud->date = $request->date;
                $stud->violation = $other;
                $stud->school_yr = $request->school_yr;
                $stud->semester = $request->semester;
                $stud->action_taken = $request->action_taken;
                $stud->minutes = $request->minutes;
                $stud->remarks = $request->remarks;

                $min = new TableMinutes;
                $min->name = $request->fname." ".$request->lname;
                $min->program = $request->program;
                $min->gender = $request->gender;
                $min->date = $request->date;
                $min->violation = $other;
                $min->school_yr = $request->school_yr;
                $min->semester = $request->semester;
                $min->action_taken = $request->action_taken;
                $min->minutes = $request->minutes;
                $min->remarks = $request->remarks;

                $vio = new ViolationList;
                $vio->violation = $other;

                $stud->save();
                $vio->save();
                $min->save();
            }

        return redirect('/svcTracer'); // Use redirect(route('svg_tracer_view_path'))->withSuccess('The case was successfully save.')
        }
    }


    public function svcCases($name)
    {
        $studs = DB::table('stud_vio_tracers')
            ->where('name', 'like', '%'.$name.'%')
            ->orderBy('created_at', 'desc')
            ->orderBy('minutes', 'desc')
            ->groupBy('violation')
            ->get();

        return view('Admin/svcCases')->with(compact('studs'));
    }

    public function svcRemarks(Request $request, $name, $violation)
    {
        $name = $request->get('name');
        $violation = $request->get('violation');

        $studs = DB::table("stud_vio_tracers")
            ->where("name", 'like', '%'.$name.'%')
            ->where("violation", 'like', '%'.$violation.'%')
            ->orderby('created_at', 'desc')
            ->orderby('minutes', 'desc')
            ->get();

        $records = DB::table("stud_vio_tracers")
            ->where("name", 'like', '%'.$name.'%')
            ->where("violation", 'like', '%'.$violation.'%')
            ->orderby('created_at', 'desc')
            ->orderby('minutes', 'desc')
            ->get();

        // $studs = DB::table("stud_vio_tracers")
        //     ->where("name", 'like', '%'.$name.'%')
        //     ->orderby('created_at', 'desc')
        //     ->orderby('minutes', 'desc')
        //     ->get();

        return view('Admin/svcRemarks')->with(compact('studs', 'records'));
    }


    public function svcShow($id)
    {
       $minutes = DB::table("table_minutes")
                ->where("id", 'like', '%'.$id.'%')
                ->get();
        return view('Admin/svcShow')->with(compact('minutes'));
    }

    public function svcEdit($id)
    {
        // I assumed table `stud_vio_tracers` uses StudVioTracer model
        // Though I'm a bit confuse why you want to get the data from
        // `stud_vio_tracers` in array, and you want to find the first one.
        $records = StudVioTracer::whereId($id)->get();
        // $stud = StudVioTracer::find($id);
        // If you insists to find the first one from the table `stud_vio_tracers`,
        // you may use this:
        $stud = $records->first(); // Since get() return's Collection

        return view('Admin/svcEdit')->with(compact('stud', 'records'));
    }

    // public function svcChange ($id)
    // {

    //     $violations = DB::table("violation_lists")
    //            ->orderBy('violation', 'asc')
    //            ->get();

    //     $programs = DB::table("programs")
    //            ->orderBy('abbreviation', 'asc')
    //            ->get();

    //    $stud = StudVioTracer::find($id);
    //    return view('Admin/svcChange')->with(compact('stud', 'violations', 'programs'));
    // }

    public function svcUpdate(Request $request, $id)
    {
        $this->validate($request, array(
            'name' => 'required',
            'program' => 'required',
            'gender' => 'required',
            'date' => 'required',
            'school_yr' => 'required',
            'action_taken' => 'required',
            'minutes' => 'required',
            'remarks' => 'required',
        ));
        // Again.

        $stud = new StudVioTracer;
        $stud->name = $request->name;
        $stud->program = $request->program;
        $stud->gender = $request->gender;
        $stud->date = $request->date;
        $stud->violation = $request->violation;
        $stud->school_yr = $request->school_yr;
        $stud->semester = $request->semester;
        $stud->action_taken = $request->action_taken;
        $stud->minutes = $request->minutes;
        $stud->remarks = $request->remarks;
        // Again.

        $min = new TableMinutes;
        $min->name = $request->name;
        $min->program = $request->program;
        $min->gender = $request->gender;
        $min->date = $request->date;
        $min->violation = $request->violation;
        $min->school_yr = $request->school_yr;
        $min->semester = $request->semester;
        $min->action_taken = $request->action_taken;
        $min->minutes = $request->minutes;
        $min->remarks = $request->remarks;
        // Again


        $min->save();
        $stud->save();

        Session::flash('success', 'The case successfully updated !');

        return redirect('/svcTracer'); // Also again.
    }

    public function svcChangeUpdate (Request $request, $id)
    {
        $this->validate($request, array(
            'name' => 'required',
            'program' => 'required',
            'gender' => 'required',
            'date' => 'required',
            'school_yr' => 'required',
            'action_taken' => 'required',
            'minutes' => 'required',
            'remarks' => 'required',
        ));
        // Again.

        $stud = StudVioTracer::find($id);
        $stud->name = $request->name;
        $stud->program = $request->program;
        $stud->gender = $request->gender;
        $stud->date = $request->date;
        $stud->violation = $request->violation;
        $stud->school_yr = $request->school_yr;
        $stud->semester = $request->semester;
        $stud->action_taken = $request->action_taken;
        $stud->minutes = $request->minutes;
        $stud->remarks = $request->remarks;

        $min = TableMinutes::find($id);
        $min->name = $request->name;
        $min->program = $request->program;
        $min->gender = $request->gender;
        $min->date = $request->date;
        $min->violation = $request->violation;
        $min->school_yr = $request->school_yr;
        $min->semester = $request->semester;
        $min->action_taken = $request->action_taken;
        $min->minutes = $request->minutes;
        $min->remarks = $request->remarks;


        $min->save();
        $stud->save();

        Session::flash('success', 'The case successfully updated !');

        return redirect('/svcTracer');
        // Also again.
    }


    // public function svcMinutes(Request $request)
    // {

    //         $name = $request->get('name');
    //         $violation = $request->get('violation');

    //         $minutes = DB::table("table_minutes")
    //             ->where("violation", 'like', '%'.$violation.'%')
    //             ->where("name", 'like', '%'.$name.'%')
    //             ->orderby('created_at', 'desc')
    //             ->orderby('minutes', 'desc')
    //             ->get();

    //         return view('Admin/svcMinutes')->with(compact('minutes'));
    // }

    public function svcMinutes(Request $request, $name, $violation)
    {
        $name = $request->get('name');
        $violation = $request->get('violation');
        $remarks = $request->get('remarks');

        $minutes = DB::table("table_minutes")
            ->where("name", 'like', '%'.$name.'%')
            ->where("violation", 'like', '%'.$violation.'%')
            ->where("remarks", 'like', '%'.$remarks.'%')
            ->orderBy('created_at', 'desc')
            ->orderBy('minutes', 'desc')
            ->get();

        return view('Admin/svcMinutes')->with(compact('minutes'));
    }

    public function minutesEdit ($id)
    {
        $minutes = TableMinutes::find($id);

        return view('Admin/minutesEdit')->with(compact('minutes'));
    }

    public function minutesUpdate (Request $request, $id)
    {
        $this->validate($request, array(
            'name' => 'required',
            'program' => 'required',
            'gender' => 'required',
            'date' => 'required',
            'school_yr' => 'required',
            'action_taken' => 'required',
            'minutes' => 'required',
            'remarks' => 'required',
        ));
        // Again.

        $stud = new TableMinutes;
        $stud->name = $request->name;
        $stud->program = $request->program;
        $stud->gender = $request->gender;
        $stud->date = $request->date;
        $stud->violation = $request->violation;
        $stud->school_yr = $request->school_yr;
        $stud->semester = $request->semester;
        $stud->action_taken = $request->action_taken;
        $stud->minutes = $request->minutes;
        $stud->remarks = $request->remarks;


        $stud->save();

        Session::flash('success', 'The minutes successfully updated !');

        return redirect('/svcTracer');
        // And again.
    }


    //pregnancy case

    public function spcForm()
    {
        $programs = Program::orderBy('abbreviation')->get();

        return view('Admin/spcForm')->with(compact('programs'));
    }

    public function spcTracer(Request $request)
    {
        $sy = "2017-2018";

        $chart = Charts::database(DB::table('stud_pregnant_tracers')
                   ->select(DB::raw('name as name, program as program, semester as semester, COUNT(*) cases'))
                   ->where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')
                   ->get(), 'bar', 'highcharts')
              ->title("Number of Unintended Pregnancy Per Course")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('program');

        $chartt = Charts::database(DB::table('stud_pregnant_tracers')
                   ->select(DB::raw('name as name, program as program, semester as semester, COUNT(*) cases'))
                   ->where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')
                   ->get(), 'bar', 'highcharts')
              ->title("Number of Unintended Pregnancy Per Course")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('program');

        $chart2 = Charts::database(StudPregnantTracer::all(), 'bar', 'highcharts')
              ->title("Number of Unintended Pregnancy Per Year")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupByYear('10');


        $programs = DB::table("programs")
           ->orderBy('abbreviation', 'asc')
           ->get();

        $studs = StudPregnantTracer::orderBy('id', 'desc')->paginate(5);

        return view('Admin/spctracer')->with(compact('studs', 'chart', 'chartt', 'chart2', 'programs', 'sy'));
    }

    public function spcTracerr(Request $request)
    {
        $sy = $request->get('year');

        $chart = Charts::database(DB::table('stud_pregnant_tracers')
                   ->select(DB::raw('name as name, program as program, semester as semester, COUNT(*) cases'))
                   ->where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')
                   ->get(), 'bar', 'highcharts')
              ->title("Number of Unintended Pregnancy Per Course")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('program');

        $chartt = Charts::database(DB::table('stud_pregnant_tracers')
                   ->select(DB::raw('name as name, program as program, semester as semester, COUNT(*) cases'))
                   ->where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')
                   ->get(), 'bar', 'highcharts')
              ->title("Number of Unintended Pregnancy Per Course")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('program');

        $chart2 = Charts::database(StudPregnantTracer::all(), 'bar', 'highcharts')
              ->title("Number of Unintended Pregnancy Per Year")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupByYear('10');


        $programs = Program::orderBy('abbreviation')->get();
        $studs = StudPregnantTracer::orderBy('id', 'desc')->paginate(5);

        return view('Admin/spctracer')->with(compact('studs', 'chart', 'chartt', 'chart2', 'programs', 'sy'));
    }

    public function spcStore(Request $request)
    {
        $this->validate($request, array(
            'fname' => 'required',
            'lname' => 'required',
            'program' => 'required',
            'date' => 'required',
            'due_date' => 'required',
            'months' => 'required',
            'school_yr' => 'required',
            'semester' => 'required',
            'action_taken' => 'required',
        ));

        $stud = new StudPregnantTracer;
        $stud->name = $request->fname." ".$request->lname;
        $stud->program = $request->program;
        $stud->date = $request->date;
        $stud->due_date = $request->due_date;
        $stud->months = $request->months;
        $stud->school_yr = $request->school_yr;
        $stud->semester = $request->semester;
        $stud->action_taken = $request->action_taken;

        $stud->save();

        Session::flash('success', 'The case successfully save !');

        return redirect('/spcTracer');
        // And Again.
    }


    public function printPreview(Request $request)
    {
        $course = $request->get('chooser');
        $salary = $request->get('salary');
        $year = $request->get('year');
        $from = $request->get('from');
        $to = $request->get('to');
        $head = Admin::all();

        if ($course == "All" && $year == "All" && $salary == "All" ){

           $courses = DB::table("alumni_tracers")
                ->orderBy('lastname', 'year_graduated')
                ->get();
                return view('/printPreview')->with(compact('courses', 'course', 'year', 'head', 'to', 'salary'));

        }
        else if ($year == "All"){

           $courses = DB::table("alumni_tracers")
                ->where("course", 'like', '%'.$course.'%')
                ->where("q_seven", 'like', '%'.$salary.'%')
                ->orderBy('lastname', 'desc')
                ->orderBy('year_graduated', 'desc')
                ->get();
                return view('/printPreview')->with(compact('courses', 'from', 'course', 'year', 'head', 'to', 'salary'));

        }
        else if ($salary == "All"){

           $courses = DB::table("alumni_tracers")
                ->where("course", 'like', '%'.$course.'%')
                ->where("year_graduated", 'like', '%'.$year.'%')
                ->orderBy('lastname', 'desc')
                ->orderBy('year_graduated', 'desc')
                ->get();
                return view('/printPreview')->with(compact('courses', 'from', 'course', 'year', 'head', 'to', 'salary'));

        }
        else if ($course == "All" && $year != "All"){

           $courses = DB::table("alumni_tracers")
                ->where("year_graduated", 'like', '%'.$year.'%')
                ->where("q_seven", 'like', '%'.$salary.'%')
                ->orderBy('lastname', 'desc')
                ->orderBy('year_graduated', 'desc')
                ->get();
                return view('/printPreview')->with(compact('courses', 'from', 'course', 'year', 'head', 'to', 'salary'));

        }
        else if ($course == "All" && $year == "All"){

           $courses = DB::table("alumni_tracers")
                ->where("q_seven", '=', $salary)
                ->orderBy('lastname', 'desc')
                ->orderBy('year_graduated', 'desc')
                ->get();
                return view('/printPreview')->with(compact('courses', 'course', 'year', 'head', 'from', 'to', 'salary'));

        }
        else if ($course == "All" && $from == "" && $to == ""){

           $courses = DB::table("alumni_tracers")
                ->where("year_graduated", 'like', '%'.$year.'%')
                ->orderBy('lastname', 'desc')
                ->orderBy('year_graduated', 'desc')
                ->get();
                return view('/printPreview')->with(compact('courses', 'from', 'course', 'year', 'head', 'to', 'salary'));

        }
        else if ($from != "" && $to != ""){

           $courses = DB::table("alumni_tracers")
                ->where("year_graduated", '<=', $to)
                ->where("year_graduated", '>=', $from)
                ->where("course", '=', $course)
                ->orderBy('lastname', 'desc')
                ->orderBy('year_graduated', 'desc')
                ->get();
                return view('/printPreview')->with(compact('courses', 'from', 'course', 'year', 'head', 'from', 'to', 'salary'));

        }
        else if ($from != "" && $to != "" && $course == "All"){

           $courses = DB::table("alumni_tracers")
                ->where("year_graduated", '<=', $to)
                ->where("year_graduated", '>=', $from)
                ->orderBy('lastname', 'desc')
                ->orderBy('year_graduated', 'desc')
                ->get();
                return view('/printPreview')->with(compact('courses', 'course', 'year', 'head', 'from', 'to', 'salary'));

        }

        else{

            $courses = DB::table("alumni_tracers")
                ->where("course", 'like', '%'.$course.'%')
                ->where("year_graduated", 'like', '%'.$year.'%')
                ->where("q_seven", 'like', '%'.$salary.'%')
                ->orderBy('lastname', 'desc')
                ->orderBy('year_graduated', 'desc')
                ->get();
                return view('/printPreview')->with(compact('courses', 'from', 'course', 'year', 'head', 'to', 'salary'));
        }
    }

    public function printPreview1(Request $request)
    {
        $program = $request->get('c');
        $year = $request->get('school_yr');
        $sem = $request->get('sem');
        $head = Admin::all();

        if ($program == "All" && $year == "All" && $sem == "All"){
           $studs = DB::table("stud_pregnant_tracers")
                ->orderBy("program", "school_yr")
                ->get();
                return view('/printpreview1')->with(compact('studs', 'program', 'year', 'head', 'sem'));
        }
        else if ($year == "All" && $sem == "All") {
           $studs = DB::table("stud_pregnant_tracers")
                ->where("program", 'like', '%'.$program.'%')
                ->get();
                return view('/printPreview1')->with(compact('studs', 'program', 'year', 'head', 'sem'));
        }
        else if ($program == "All" && $sem == "All") {
           $studs = DB::table("stud_pregnant_tracers")
                ->where("school_yr", 'like', '%'.$year.'%')
                ->get();
                return view('printpreview1')->with(compact('studs', 'program', 'year', 'head', 'sem'));
        }

// All choces

        else if ($program == "All" && $year == "All"){

           $studs = DB::table("stud_pregnant_tracers")
                ->where("semester", 'like', '%'.$sem.'%')
                ->orderBy("program", "school_yr")
                ->get();
                return view('/printpreview1')->with(compact('studs', 'program', 'year', 'head', 'sem'));

        }
        else if ($year == "All"){

           $studs = DB::table("stud_pregnant_tracers")
                ->where("program", 'like', '%'.$program.'%')
                ->where("semester", 'like', '%'.$sem.'%')
                ->get();
                return view('/printpreview1')->with(compact('studs', 'program', 'year', 'head', 'sem'));

        }
        else if ($program == "All"){

           $studs = DB::table("stud_pregnant_tracers")
                ->where("school_yr", 'like', '%'.$year.'%')
                ->where("semester", 'like', '%'.$sem.'%')
                ->get();
                return view('/printpreview1')->with(compact('studs', 'program', 'year', 'head', 'sem'));

        }

        else{

            $studs = DB::table("stud_pregnant_tracers")
                ->where("program", 'like', '%'.$program.'%')
                ->where("school_yr", 'like', '%'.$year.'%')
                ->where("semester", 'like', '%'.$sem.'%')
                ->get();
                return view('/printpreview1')->with(compact('studs', 'program', 'year', 'head', 'sem'));
        }


    }

    public function printPreview2(Request $request)
    {


            // $studs = StudVioTracer::all();
            // return view('/printPreview2')->with(compact('studs'));
         $course = $request->get('c');
         $violation = $request->get('violation');
         $year = $request->get('school_yr');
         $sem = $request->get('sem');

         $head = Admin::all();


        if ($course == "All" && $year == "All" && $violation == "All" && $sem == "All"){

           $studs = DB::table("stud_vio_tracers")
                ->orderBy("program", "school_yr", "violation")
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));

        }
        else if ($year == "All" && $violation == "All" && $sem == "All"){

           $studs = DB::table("stud_vio_tracers")
                ->where("program", 'like', '%'.$course.'%')
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));

        }

        else if ($course == "All" && $year == "All" && $sem == "All"){

           $studs = DB::table("stud_vio_tracers")
                ->where("violation", 'like', '%'.$violation.'%')
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));

        }

         else if ($violation == "All" && $course == "All" && $sem == "All"){

           $studs = DB::table("stud_vio_tracers")
                ->orderBy("program", "school_yr", "violation")
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));

        }

        else if ($year == "All" && $sem == "All"){

           $studs = DB::table("stud_vio_tracers")
                ->where("program", 'like', '%'.$course.'%')
                ->where("violation", 'like', '%'.$violation.'%')
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));

        }
        else if ($course == "All" && $sem == "All"){


           $studs = DB::table("stud_vio_tracers")
                ->where("school_yr", 'like', '%'.$year.'%')
                ->where("violation", 'like', '%'.$violation.'%')
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));

        }
        else if ($violation == "All" && $sem == "All"){

           $studs = DB::table("stud_vio_tracers")
                ->where("school_yr", 'like', '%'.$year.'%')
                ->where("program", 'like', '%'.$course.'%')
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));

        }

// when choces all


        if ($course == "All" && $year == "All" && $violation == "All"){

           $studs = DB::table("stud_vio_tracers")
                ->where("semester", 'like', '%'.$sem.'%')
                ->orderBy("program", "school_yr", "violation")
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));

        }
        else if ($year == "All" && $violation == "All"){

           $studs = DB::table("stud_vio_tracers")
                ->where("program", 'like', '%'.$course.'%')
                ->where("semester", 'like', '%'.$sem.'%')
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));

        }

        else if ($course == "All" && $year == "All"){

           $studs = DB::table("stud_vio_tracers")
                ->where("violation", 'like', '%'.$violation.'%')
                ->where("semester", 'like', '%'.$sem.'%')
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));

        }

         else if ($violation == "All" && $course == "All"){

           $studs = DB::table("stud_vio_tracers")
                ->where("semester", 'like', '%'.$sem.'%')
                ->where("school_yr", 'like', '%'.$year.'%')
                ->orderBy("program", "school_yr", "violation")
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));

        }

        else if ($year == "All"){

           $studs = DB::table("stud_vio_tracers")
                ->where("program", 'like', '%'.$course.'%')
                ->where("violation", 'like', '%'.$violation.'%')
                ->where("semester", 'like', '%'.$sem.'%')
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));

        }
        else if ($course == "All"){


           $studs = DB::table("stud_vio_tracers")
                ->where("school_yr", 'like', '%'.$year.'%')
                ->where("violation", 'like', '%'.$violation.'%')
                ->where("semester", 'like', '%'.$sem.'%')
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));

        }
        else if ($violation == "All"){

           $studs = DB::table("stud_vio_tracers")
                ->where("school_yr", 'like', '%'.$year.'%')
                ->where("program", 'like', '%'.$course.'%')
                ->where("semester", 'like', '%'.$sem.'%')
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));

        }

// when choces 1st

        else{

            $studs = DB::table("stud_vio_tracers")
                ->where("program", 'like', '%'.$course.'%')
                ->where("school_yr", 'like', '%'.$year.'%')
                ->where("violation", 'like', '%'.$violation.'%')
                ->where("semester", 'like', '%'.$sem.'%')
                ->get();
                return view('/printpreview2')->with(compact('studs', 'course', 'year', 'violation', 'head', 'sem'));
        }
    }

    public function printPreview3(Request $request)
    {


            $program = $request->get('chooser');
            $year = $request->get('year');

            $head = Admin::all();


        if ($program == "All" && $year == "All"){

           $courses = DB::table("graduate_per_courses")
                ->orderBy("date_graduated")
                ->get();
                return view('/printPreview3')->with(compact('courses', 'program', 'year', 'head'));

        }
        if ($year == "All"){

           $courses = DB::table("graduate_per_courses")
                ->where("program", 'like', '%'.$program.'%')
                ->get();
                return view('/printPreview3')->with(compact('courses', 'program', 'year', 'head'));

        }
        if ($program == "All"){

           $courses = DB::table("graduate_per_courses")
                ->where("date_graduated", 'like', '%'.$year.'%')
                ->get();
                return view('/printPreview3')->with(compact('courses', 'program', 'year', 'head'));

        }
        else{

            $courses = DB::table("graduate_per_courses")
                ->where("program", 'like', '%'.$program.'%')
                ->where("date_graduated", 'like', '%'.$year.'%')
                ->get();
                return view('/printPreview3')->with(compact('courses', 'program', 'year', 'head'));
        }

    }

    public function printPreview4(Request $request)
    {


            $program = $request->get('chooser');
            $year = $request->get('year');

            $head = Admin::all();




    if ($program == "All" && $year == "All"){


        $programs = DB::table("programs")
               ->orderBy('abbreviation', 'asc')
               ->get();

        $progs = GraduatePerCourse::all();

        $course = Program::all();


        $counts = DB::table('alumni_tracers')
            ->select(DB::raw('year_graduated as year_graduated, course as course, firstname as firstname, lastname as lastname'))
            ->groupBy('firstname', 'lastname')
            ->orderby('year_graduated', 'desc')
            ->get();

        $prev_name = "";
        $prev_prog = 0;
        $year_graduated_name = "";
        $prev_totalprog = 0;
        $total_male = 0;
        $total_female = 0;

    return view('/printPreview4')->with(compact('alumnus', 'progs', 'counts', 'prev_name', 'prev_prog', 'course', 'year_graduated_name', 'programs', 'head'));
        }
    }


     public function printStudMinutes($id)
    {

       $minutes = DB::table("table_minutes")
                ->where("id", 'like', '%'.$id.'%')
                ->get();
        return view('/printStudMinutes')->with(compact('minutes'));

    }


    public function file()
    {

        $sy = SchoolYear::all();

        $orgs = DB::table('org_file_tables')
                   ->select(DB::raw('org_name as org_name, COUNT(*) no_files'))
                   ->groupBy('org_name')
                   ->orderByRaw('min(org_name) asc')
                   ->get();

        $offs = DB::table('staff_file_tables')
                   ->select(DB::raw('office_name as office_name, COUNT(*) no_files'))
                   ->groupBy('office_name')
                   ->orderByRaw('min(office_name) asc')
                   ->get();

        return view('Admin/file')->with(compact('orgs', 'offs', 'sy'));


    }

    public function orgFileView($org_name, $schl_year)
    {

            $sy = $schl_year;

            $orgs = DB::table("org_file_tables")
                ->where("org_name", 'like', '%'.$org_name.'%')
                ->where("school_year", '=', $sy)
                ->orderby('created_at', 'desc')
                ->get();

        if($school_year = request('school_year') && $org_name = request('org_name') ) {

        $orgs = OrgFileTable::where('school_year', "=", request('school_year'))
            ->where('org_name', "=", request('org_name'))
            ->orderby('created_at', 'desc')
            ->get();

          }

          $archives = DB::table('org_file_tables')
          ->select(DB::raw('school_year as school_year, org_name as org_name, COUNT(*) file_count'))
                   ->groupBy('school_year')
                   ->orderByRaw('min(created_at) asc')
                   ->get();

            return view('Admin/orgFileView')->with(compact('orgs', 'archives'));
    }

    public function staffFileView($office_name, $schl_year)
    {

            $sy = $schl_year;

            $offs = DB::table("staff_file_tables")
                ->where("office_name", 'like', '%'.$office_name.'%')
                ->where("school_year", '=', $sy)
                ->orderby('school_year', 'desc')
                ->get();

        if($school_year = request('school_year') && $office_name = request('office_name') ) {

        $offs = StaffFileTable::where('school_year', "=", request('school_year'))
            ->where('office_name', "=", request('office_name'))
            ->orderby('created_at', 'desc')
            ->get();

          }

          $archives = DB::table('staff_file_tables')
          ->select(DB::raw('school_year as school_year, office_name as office_name, COUNT(*) file_count'))
                   ->groupBy('school_year')
                   ->orderByRaw('min(created_at) asc')
                   ->get();

            return view('Admin/staffFileView')->with(compact('offs', 'archives'));
    }


    public function destroyy($id)
    {
        $prog = Program::find($id);
        $prog->delete();

        Session::flash('success', 'The program successfully deleted !');

        return redirect('/conPanel');
    }

    public function autocompleteFname(Request $request)
    {
        $data = studMasterList::select('firstname as name')->where("firstname","LIKE","%{$request->input('query')}%")->get();
        return response()->json($data);
    }

    public function autocompleteTwo(Request $request)
    {
        $data = studMasterList::select('lastname as name')->where("lastname","LIKE","%{$request->input('query')}%")->get();
        return response()->json($data);
    }

public function printPreview5(Request $request)
    {

        $sy = $request->get('p_year');

        $chart = Charts::database(DB::table('stud_pregnant_tracers')
                   ->select(DB::raw('name as name, program as program, semester as semester, COUNT(*) cases'))
                   ->where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')
                   ->get(), 'bar', 'highcharts')
              ->title("Number of Unintended Pregnancy Per Course")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('program');

        $chartt = Charts::database(DB::table('stud_pregnant_tracers')
                   ->select(DB::raw('name as name, program as program, semester as semester, COUNT(*) cases'))
                   ->where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')
                   ->get(), 'bar', 'highcharts')
              ->title("Number of Unintended Pregnancy Per Course")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('program');

        $chart2 = Charts::database(StudPregnantTracer::all(), 'bar', 'highcharts')
              ->title("Number of Unintended Pregnancy Per Year")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupByYear('10');


        $programs = DB::table("programs")
           ->orderBy('abbreviation', 'asc')
           ->get();

        $head = Admin::all();

        $studs = StudPregnantTracer::orderBy('id', 'desc')->paginate(5);
        return view('/printPreview5')->with(compact('studs', 'chart', 'chartt', 'chart2', 'programs', 'sy', 'head'));

    }

    public function printPreview6(Request $request)
    {

         $sy = $request->get('p_year');

         $chart = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('name as name, program as program, violation as violation, COUNT(*) cases'))
                   ->where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases Per Course")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('program');

         $chartt = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('name as name, program as program, violation as violation, COUNT(*) cases'))
                   ->where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases Per Course")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('program');

        $chart1 = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('name as name, program as program, violation as violation, COUNT(*) cases'))
                   ->where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases Per Violation")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('violation');

        $chart11 = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('name as name, program as program, violation as violation, COUNT(*) cases'))
                   ->where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases Per Violation")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('violation');

        $chart2 = Charts::database(StudVioTracer::all(), 'bar', 'highcharts')
              ->title("Number of Cases Per Year")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupByYear(10);

        $chart3 = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('gender as gender, COUNT(*) cases'))
                   ->where("semester", '=', "1st")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases By Gender")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('gender');

        $chart33 = Charts::database(DB::table('stud_vio_tracers')
                   ->select(DB::raw('gender as gender, COUNT(*) cases'))
                   ->where("semester", '=', "2nd")
                   ->where("school_yr", 'like','%'.$sy.'%')
                   ->groupBy('name', 'program')->get(), 'bar', 'highcharts')
              ->title("Number of Cases By Gender")
              ->elementLabel("Total")
              ->responsive(false)
              ->height(400)
              ->width(900)
              ->groupBy('gender');


        $studs = DB::table('stud_vio_tracers')
                   ->select(DB::raw('name as name, program as program, violation as violation, COUNT(violation) cases'))
                   ->groupBy('name', 'program')
                   ->orderByRaw('min(created_at) desc')
                   ->get();

        $counts = DB::table('stud_vio_tracers')
            ->select(DB::raw('name as name, program as program, violation as violation'))
            ->groupBy('name')
            ->get();

        $course = Program::all();
        $casess = 0;
        $prev_name = "";

        $violations = DB::table("violation_lists")
                   ->orderBy('violation', 'asc')
                   ->get();

        $programs = DB::table("programs")
                   ->orderBy('abbreviation', 'asc')
                   ->get();

        $head = Admin::all();

        return view('/printPreview6')->with(compact('studs', 'violations', 'chart', 'chart1', 'chart2', 'chart3', 'chartt', 'chart11', 'chart22', 'chart33', 'programs', 'counts', 'sy', 'head'));

    }

    public function adminNameChange(Request $request)
    {
        $this->validate($request, array(
            'osa_heads' => 'required',
        ));
        // Again.

        $admin = Admin::find(1);
        $admin->name = $request->osa_heads;

        $admin->save();

        Session::flash('success', 'save !');

        return redirect('/conPanel'); // Again

    }

    public function syChange(Request $request)
    {
        $this->validate($request, array(
            'school_year' => 'required',
        ));

        $sy = SchoolYear::find(1);
        $sy->schl_year = $request->school_year;

        $sy->save();

        Session::flash('success', 'save !');

        return redirect('/conPanel'); // Again

    }

    public function alumniDataPrint($id){

        $alums = AlumniTracer::find($id);
        return view('/alumniDataPrint')->with(compact('alums'));
    }

    public function orgDelete($id)
    {
        $org = OrgUser::find($id);
        $org->delete();

        Session::flash('success', 'The account successfully deleted !');

        return redirect('/conPanel'); // Again
    }

    public function staffDelete($id)
    {
        $staff = StaffUser::find($id);
        $staff->delete();

        Session::flash('success', 'The account successfully deleted !');

        return redirect('/conPanel'); //  Again
    }
}
