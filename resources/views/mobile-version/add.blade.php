@extends('layouts.app')

@section('body')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">

                    <div class="card-header">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
                            <h6>New Mobile Version</h6>
                            <div class="btn-toolbar mb-2 mb-md-0">
                                <div class="btn-group me-2">

                                    <a href="{{route('mobile_version_list')}}" title="" class="module_button_header">
                                        <button type="button" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-list"></i> {{__('messages.list')}}
                                        </button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @include('layouts.message')

                        <div class="row">
                            <div class="col-md-12">
                                {{ html()
                                    ->form('POST', route('mobile_version_store'))
                                    ->attribute('enctype', 'multipart/form-data')
                                    ->attribute('files', true)
                                    ->attribute('autocomplete', 'off')
                                    ->open()
                                }}
                                <div class="row">

                                    <div class="form-group row mg-top">
                                        <div class="col-sm-2">
                                            <label for="mobile_app_id" class="form-label">Mobile App</label>
                                            <span class="textRed">*</span>
                                        </div>

                                        <div class="col-sm-10">
                                            {{ html()
                                                ->select('mobile_app_id', $mobileApps, old('mobile_app_id'))
                                                ->class('form-control form-select js-example-basic-single')
                                                ->attribute('aria-describedby', 'basic-addon2')
                                                ->placeholder("Choose mobile app")
                                                ->required()
                                            }}
                                            <span class="textRed">{!! $errors->first('mobile_app_id') !!}</span>
                                        </div>
                                    </div>

                                    <div class="form-group row mg-top">
                                        <div class="col-sm-2">
                                            <label for="mobile_version" class="form-label">Mobile Version</label>
                                            <span class="textRed">*</span>
                                        </div>

                                        <div class="col-sm-4">
                                            {{html()
                                                ->text('mobile_version')
                                                ->class('form-control')
                                                ->placeholder("Mobile Version")
                                                ->required()
                                            }}
                                            <span class="textRed">{!! $errors->first('mobile_version') !!}</span>
                                        </div>

                                        <hr style="margin-top: 20px">

                                        <div class="col-sm-2">
                                            <label for="minimum_plugin_version" class="form-label">Minimum plugin version</label>
                                            <span class="textRed">*</span>
                                        </div>

                                        <div class="col-sm-4">
                                            {{html()
                                                ->text('minimum_plugin_version')
                                                ->class('form-control')
                                                ->placeholder("Minimum plugin version")
                                                ->required()
                                            }}
                                            <span class="textRed">{!! $errors->first('minimum_plugin_version') !!}</span>
                                        </div>

                                        <div class="col-sm-2">
                                            <label for="latest_plugin_version" class="form-label">Latest plugin version</label>
                                            <span class="textRed">*</span>
                                        </div>

                                        <div class="col-sm-4">
                                            {{html()
                                                ->text('latest_plugin_version')
                                                ->class('form-control')
                                                ->placeholder("Latest plugin version")
                                                ->required()
                                            }}
                                            <span class="textRed">{!! $errors->first('latest_plugin_version') !!}</span>
                                        </div>
                                    </div>

                                    <hr style="margin-top: 20px">


                                    <div class="form-group row mg-top">
                                        <div class="col-sm-2">
                                            <label for="optional_message" class="form-label">Optional message</label>
                                        </div>

                                        <div class="col-sm-4">
                                            {{html()
                                                ->textarea('optional_message')
                                                ->class('form-control')
                                                ->placeholder("Optional message")
                                                ->attribute('rows',5)
                                            }}
                                            <span class="textRed">{!! $errors->first('optional_message') !!}</span>
                                        </div>
                                    </div>

                                    <hr style="margin-top: 20px">


                                    <div class="form-group row mg-top">
                                        <div class="col-sm-2">
                                            <label for="" class="form-label">Force update</label>
                                        </div>

                                        <div class="col-sm-4">
                                            <div class="from-group">
                                                <?php
                                                $pluginIsPremiumTrue = '';
                                                $pluginIsPremiumFalse = '';
                                                if (isset($data->force_update)) {
                                                    if ($data->force_update == 1) {
                                                        $pluginIsPremiumTrue = 'checked="checked"';
                                                    } else {
                                                        $pluginIsPremiumFalse = 'checked="checked"';
                                                    }
                                                } else {
                                                    $pluginIsPremiumFalse = 'checked="checked"';
                                                }
                                                ?>
                                                <div class="input-group mb-3">
                                                    <div class="form-check form-check-inline">
                                                        <input style="margin-top: 0px"
                                                               class="form-check-input isChecked"
                                                               type="radio" name="force_update" id="showNoDataViewTrue"
                                                               value="1" {{$pluginIsPremiumTrue}}>
                                                        <label class="form-check-label" for="showNoDataViewTrue">True</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input style="margin-top: 0px"
                                                               class="form-check-input isChecked"
                                                               type="radio" name="force_update" id="showNoDataViewFalse"
                                                               value="0" {{$pluginIsPremiumFalse}}>
                                                        <label class="form-check-label" for="showNoDataViewFalse">False</label>
                                                    </div>
                                                    <span class="textRed">{!! $errors->first('force_update') !!}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mg-top">
                                        <div class="col-md-2"></div>
                                        <div class="col-md-10" >
                                            <div class="from-group">
                                                <button type="submit" class="btn btn-primary " id="UserFormSubmit">Submit</button>
                                                <button type="reset" class="btn submit-button">Reset</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{ html()->form()->close() }}
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('CustomStyle')
    <style>
        .textRed{
            color: #ff0000;
        }

    </style>
@endpush

@section('footer.scripts')
    <script type="text/javascript">
        const zipInp = document.getElementById("zipInp");
        const fileName = document.getElementById("fileName");

        zipInp.onchange = () => {
            if (zipInp.files.length > 0) {
                fileName.textContent = "Selected file: " + zipInp.files[0].name;
            } else {
                fileName.textContent = "";
            }
        };
    </script>

@endsection
