<?php

namespace App\Http\Controllers;

use App\Traits\GlobalTrait;
use App\Traits\PhraseTrait;
use Illuminate\Http\Request;
use Validator;

class PhraseController extends Controller
{
    use PhraseTrait, GlobalTrait;

    public function PhraseAnalyser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "query" => "required|max:255"
            ]);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $query = request()->query('query');
            $chars = str_split($query);
            $count = 0;
            $countIndexArray = array_count_values($chars);
            $countValueArray = array_keys($countIndexArray);

            foreach ($countIndexArray as $char => $count) {
                echo $this->outPut($char, $count, $chars);
            }

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    protected function outPut($char, $count, $chars)
    {
        if ($count > 1)
            return $char . ': ' . $count . ': before : (' . $this->getBefore($char, $chars) . ') after: (' . $this->getAfters($char, $chars) . ') :max-distance:'.$this->maxDistnce($char, $chars).'<br>';
        else
            return $char . ': ' . $count . ': before : (' . $this->getBefore($char, $chars) . ') after: (' . $this->getAfters($char, $chars) . ') <br>';

    }

}
