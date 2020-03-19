<!-- Modal -->
<div class="modal fade in" id="add_to_timer_Modal{{$category -> id}}" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><span id="">{{$category -> name_ar}}</span></h4>
            </div>
            <form action="{{Route('admin.offerCategories.addToTimer')}}" method="Post">
                {{csrf_field()}}
                <div class="modal-body">
                    <h5>قم بأدخال مده القسم: </h5>
                    <div class="row">
                        <div class="form-group has-float-label col-sm-4">
                            <input onkeypress="return event.charCode >= 48" type="number"
                                   min="0"
                                   class="form-control form-control  {{$errors->has('hours') ? 'redborder' : ''}}"
                                   name="hours"
                                   value="{{ old( 'hours', $category->hours) }}"
                                   placeholder="ساعة "/>
                            <small class="text-danger">
                                @error('hours')
                                {{$message}}
                                @enderror
                            </small>
                        </div>

                        <div class="form-group has-float-label col-sm-4">
                            <input onkeypress="return event.charCode >= 48" type="number"
                                   min="0"
                                   class="form-control form-control  {{$errors->has('minutes') ? 'redborder' : ''}}"
                                   name="minutes"
                                   value="{{ old( 'minutes', $category->minutes) }}"
                                   placeholder="دقيقة "/>
                            <small class="text-danger">
                                @error('minutes')
                                {{$message}}
                                @enderror
                            </small>
                        </div>

                        <div class="form-group has-float-label col-sm-4">
                            <input onkeypress="return event.charCode >= 48"  type="number"
                                   min="0"
                                   class="form-control form-control  {{$errors->has('seconds') ? 'redborder' : ''}}"
                                   name="seconds"
                                   value="{{ old( 'seconds', $category->seconds) }}"
                                   placeholder="ثانية "/>
                            <small class="text-danger">
                                @error('seconds')
                                {{$message}}
                                @enderror
                            </small>
                        </div>


                    </div>

                    <input type="hidden" class="form-control" name="category_id" id="categoryId" value="{{$category -> id}}"
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
