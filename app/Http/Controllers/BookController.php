<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Models\BookReview;
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use function Psy\debug;

class BookController extends Controller
{
    // public function index()
    // {
    //     //$books = Book::all();
    //     $books = Book::with('bookDownload', 'category', 'editorial', 'authors')->get();
    //     return [
    //         "error" => false,
    //         "message" => "Successfull",
    //         "data" => $books
    //     ];
    // }

    public function index()
    {
        $books = Book::orderBy('title', 'asc')->get();
        return $this->getResponse200($books);
    }



    public function response()
    {
        return [
            "error" => true,
            "message" => "Wrong action!",
            "data" => []
        ];
    }





    public function store(Request $request)
    {
        $isbn = trim($request->isbn);
        $existIsbn = Book::where("isbn", trim($request->isbn))->exists();
        if (!$existIsbn) {
            $book = new Book();
            $book->isbn = $isbn;
            $book->title = $request->title;
            $book->description = $request->description;
            $book->published_date = Carbon::now();
            $book->category_id = $request->category["id"];
            $book->editorial_id = $request->editorial["id"];
            $book->save();
            foreach ($request->authors as $item) {
                $book->authors()->attach($item);
            }
           return $this->getResponse201("Book", "Created", $book);
        } else {
            return $this->getResponse404();

        }
    }

    public function update(Request $request, $id)
    {
        $reponse = $this->response();
        $book = Book::find($id);

        DB::beginTransaction();
        try{

            if ($book){
                $isbn = trim($request->isbn);
                $isbnOwner = Book::where("isbn", $isbn)->first();

                if ( !$isbnOwner || $isbnOwner->id == $book->id) {
                    $book->isbn = $isbn;
                    $book->title = $request->title;
                    $book->description = $request->description;
                    $book->published_date = Carbon::now();
                    $book->category_id = $request->category["id"];
                    $book->editorial_id = $request->editorial["id"];
                    $book->update();
                    //Delete
                    foreach ($book->authors as $item) {
                        $book->authors()->detach($item->id);
                    }
                    foreach ($request->authors as $item) {
                        $book->authors()->attach($item);
                    }
                    $book = Book::with('category', 'editorial', 'authors')->where("id",$id)->get();
                    //$response["error"] = false;
                    //$response["message"] = "Your book has been updated!";
                    //$response["data"] = $book;

                    return $this->getResponse201("Book", "Updated", $book);
                } else {
                    $reponse["message"] = "ISBN duplicated!";
                }
            } else {
                $reponse["message"] = "Not found";
            }

            DB::commit();
        }catch(Exception $e){
            return $e;
            $response["message"] = "Rollback transaction";
            DB::rollBack();
        }


    }

    public function show($id){
        $book = Book::find($id);
        // return [
        //     "error" => false,
        //     "message" => "This is the book!",
        //     "data" => $book
        // ];

        return $this->getResponse200($book);
    }

    public function addBookReview(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required'
        ]);
        if (!$validator->fails()) {
            DB::beginTransaction();
            try {
                $bookReview = new BookReview();
                $bookReview->comment = trim($request->comment);
                $bookReview->book_id = $id;
                $bookReview->user_id = auth()->user()->id;
                $bookReview->edited = false;
                $bookReview->save();

                $bookReview = BookReview::with("book", 'user')
                    ->where('user_id', auth()->user()->id)
                    ->where('book_id', $id)
                    ->first();

                DB::commit();

                return $this->getResponse201(
                    "book review",
                    "created",
                    $bookReview
                );
            } catch (Exception $err) {
                DB::rollBack();
                return $this->getResponse500([$err->getMessage()]);
            }
        } else {
            return $this->getResponse500([$validator->errors()]);
        }
    }

    public function updateBookReview(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required'
        ]);
        if (!$validator->fails()) {
            DB::beginTransaction();
            try {
                $bookReview = BookReview::with("book", 'user')->where('id', $id)->first();
                if ($bookReview) {
                    if ($bookReview->user_id == auth()->user()->id) {
                        $bookReview->comment = trim($request->comment);
                        $bookReview->edited = true;
                        $bookReview->update();

                        $bookReview = BookReview::with("book", 'user')->where('id', $id)->first();

                        DB::commit();

                        return $this->getResponse201(
                            "book review",
                            "updated",
                            $bookReview
                        );
                    } else {
                        return $this->getResponse403();
                    }
                } else {
                    return $this->getResponse404();
                }
            } catch (Exception $err) {
                DB::rollBack();
                return $this->getResponse500([$err->getMessage()]);
            }
        } else {
            return $this->getResponse500([$validator->errors()]);
        }
    }
}
