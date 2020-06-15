public
function getProviderDoctors(Request $request)
{  // main provider
$validator = Validator::make($request->all(), [
"id" => "required|numeric",
]);
if ($validator->fails()) {
$code = $this->returnCodeAccordingToInput($validator);
return $this->returnValidationError($code, $validator);
}
$validation = $this->validateFields(['specification_id' => $request->specification_id, 'nickname_id' => $request->nickname_id,
'provider_id' => $request->provider_id, 'branch' => ['main_provider_id' => $request->id, 'provider_id' => $request->provider_id, 'branch_no' => 0]]);

$provider = $this->getProviderByID($request->id);

if ($provider != null) {
if ($provider->provider_id != null) {
$request->provider_id = 0;
$branchesIDs = [$provider->id];
} else {
$branchesIDs = $provider->providers()->pluck('id');
}

if (count($branchesIDs) > 0) {
if (isset($request->specification_id) && $request->specification_id != 0) {
if ($validation->specification_found == null)
return $this->returnError('D000', trans('messages.There is no specification with this id'));
}
if (isset($request->nickname_id) && $request->nickname_id != 0) {
if ($validation->nickname_found == null)
return $this->returnError('D000', trans('messages.There is no nickname with this id'));
}
if (isset($request->provider_id) && $request->provider_id != 0) {
if ($validation->provider_found == null)
return $this->returnError('D000', trans('messages.There is no branch with this id'));

if ($validation->branch_found)
return $this->returnError('D000', trans("messages.This branch isn't in your branches"));
}
if (isset($request->gender) && $request->gender != 0 && !in_array($request->gender, [1, 2])) {
return $this->returnError('D000', trans("messages.This is invalid gender"));
}

$front = $request->has('show_front') ? 1 : 0;
$doctors = $this->getDoctors($branchesIDs, $request->specification_id, $request->nickname_id, $request->provider_id, $request->gender, $front);

if (count($doctors) > 0) {
foreach ($doctors as $key => $doctor) {

$doctor->time = "";
$days = $doctor->times;
$match = $this->getMatchedDateToDays($days);

if (!$match || $match['date'] == null) {
$doctor->time = new \stdClass();;
continue;
}
$doctorTimesCount = $this->getDoctorTimePeriodsInDay($match['day'], $match['day']['day_code'], true);
$availableTime = $this->getFirstAvailableTime($doctor->id, $doctorTimesCount, $days, $match['date'], $match['index']);
$doctor->time = $availableTime;


$doctor->branch_name = Doctor::find($doctor->id)->provider->{'name_' . app()->getLocale()};
}
$total_count = $doctors->total();
$doctors->getCollection()->each(function ($doctor) {
$doctor->makeVisible(['name_en', 'name_ar', 'information_en', 'information_ar']);
return $doctor;
});


$doctors = json_decode($doctors->toJson());
$doctorsJson = new \stdClass();
$doctorsJson->current_page = $doctors->current_page;
$doctorsJson->total_pages = $doctors->last_page;
$doctorsJson->total_count = $total_count;
$doctorsJson->data = $doctors->data;
return $this->returnData('doctors', $doctorsJson);
}
return $this->returnData('doctors', $doctors);
}
return $this->returnError('E001', trans('messages.There are no branches for this provider'));
}
return $this->returnError('E001', trans('messages.There is no provider with this id'));

}
