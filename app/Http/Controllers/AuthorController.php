<?php

namespace App\Http\Controllers;




use App\Models\Author;
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthorController extends Controller
{
    public function index()
    {
        $authors = Author::orderBy('name', 'asc')->get();
        return $this->getResponse200($authors);
    }




    public function store(Request $request)
    {
        //$isbn = trim($request->isbn);
            $author = new Author();
            $author->name = $request->name;
            $author->first_surname = $request->first_surname;
            $author->second_surname = $request->second_surname;
            $author->save();
           return $this->getResponse201("Author", "Created", $author);
    }





    public function update(Request $request, $id)
    {
        $author = Author::find($id);

            if ($author){
                $author->name = $request->name;
                $author->first_surname = $request->first_surname;
                $author->second_surname = $request->second_surname;
                $author->update();

                return $this->getResponse201("author", "updated", $author);

            } else {
                return $this->getResponse404();
            }

    }




    public function show($id){
        $author = Author::find($id);

        if($author){
            return $this->getResponse200($author);
        }else{
            return $this->getResponse404();
        }



    }

}


