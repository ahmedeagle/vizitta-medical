<!-- Modal -->
<div class="modal fade in" id="edit_rate_Modal{{$reservation -> id}}" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><span id="">تعديل التقييم </span></h4>
            </div>
            <form action="{{Route('admin.comments.update')}}" method="Post">
                {{csrf_field()}}
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group has-float-label col-sm-6">
                            <label for="name_en">تقييم مقدم الخدمة <span class="astric">*</span></label>
                            <input type="text"
                                   min="0"
                                   class="form-control   {{$errors->has('provider_rate') ? 'redborder' : ''}}"
                                   name="provider_rate"
                                   value="{{@$reservation-> provider_rate }}"
                                   placeholder="تقييم مقدم الخدمة "/>
                            <small
                                class="text-danger">{{ $errors->has('provider_rate') ? $errors->first('provider_rate') : '' }}</small>
                        </div>

                        <div class="form-group has-float-label col-sm-6">
                            <label for="name_en">تقييم الطبيب <span class="astric">*</span></label>
                            <input type="text"
                                   min="0"
                                   class="form-control   {{$errors->has('doctor_rate') ? 'redborder' : ''}}"
                                   name="doctor_rate"
                                   value="{{@$reservation-> doctor_rate }}"
                                   placeholder=" تقييم الطبيب "/>
                            <small
                                class="text-danger">{{ $errors->has('doctor_rate') ? $errors->first('doctor_rate') : '' }}</small>
                        </div>

                        <div class="form-group has-float-label col-12">
                            <label for="name_en">نص اللتقييم <span class="astric">*</span></label>
                            <textarea type="text"
                                      class="form-control   {{$errors->has('rate_comment') ? 'redborder' : ''}}"
                                      name="rate_comment"
                            >{{ @$reservation-> 	rate_comment }}</textarea>
                            <small
                                class="text-danger">{{ $errors->has('rate_comment') ? $errors->first('rate_comment') : '' }}</small>
                        </div>

                    </div>

                    <input type="hidden" class="form-control" name="reservation_id"
                           value="{{$reservation -> id}}"
                           placeholder="  "/>
                </div>
                <div class="modal-footer">
                    <button type="button" style="margin-left: 10px;"
                            class="add-btn-list btn btn-danger " data-dismiss="modal"> تراجع
                    </button>
                    <button type="submit" style="margin-left: 10px;"
                            class="add-btn-list btn btn-success confirmAddCategoryToTimer"> اضافة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
