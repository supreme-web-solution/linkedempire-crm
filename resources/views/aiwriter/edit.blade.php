@extends('layout.auth')

@section('content')
<h2 class="text-lg font-semibold text-gray-700">
    Update Content - {{$aicontent->title}}
</h2>
<div class="md:flex gap-6 mb-8">
    <div class="w-1/2 rounded-lg">
        <ul class="">
            <li class="mb-4">
                <span class="cursor-pointer text-indigo-600 section-btn" id="cold-mail">
                    First cold email
                </span>
            </li>
            <li class="mb-4">
                <span class="cursor-pointer section-btn" id="linkedin">
                    LinkedIn connection message
                </span>
            </li>
            <li class="cursor-pointer mb-4">
                <span class="cursor-pointer section-btn" id="ice-breaker">
                    Personalized ice-breaker
                </span>
            </li>
        </ul>
    </div>
    <div class="w-full p-4 bg-white rounded-lg shadow-xs">
        <div class="mt-4 w-full">
            <div class="p-4 mb-4 mt-4 text-sm text-red-800 rounded-lg bg-red-100 hidden" id="notification" role="alert">
                <span class="font-medium">Error!</span>
                <span class="notify-message"></span>
            </div>
            <form action="{{route('aiwriter.update', ['id' => $aicontent->id])}}" method="post">
                @csrf
                @method('PUT')
                <input type="hidden" id="aitype" name="aitype" value="{{$aicontent->ai_type}}">
                <label class="block text-sm w-full">
                    <span class="text-gray-700">Title</span>
                    <input
                    type="text"
                    class="block w-full mt-1 text-sm                   focus:border-indigo-400 focus:outline-none 
                    focus:shadow-outline-indigo form-input rounded-md border-gray-200"
                    placeholder="Content title"
                    id="title" name="title"
                    value="{{$aicontent->title}}"
                    required
                    />
                </label>
                <label class="block mt-4 mb-4 text-sm">
                    <span class="text-gray-700">
                        Language
                    </span>
                    <select class="block w-full mt-1 text-sm 
                  form-select rounded-md
                    focus:border-indigo-400 focus:outline-none 
                    focus:shadow-outline-indigo
                    id="language"
                    name="language"
                    value="{{$aicontent->language}}">
                        <option value="English">English</option>
                        <option value="Romanian">Romanian</option>
                        <option value="Italian">Italian</option>
                        <option value="French">French</option>
                        <option value="Spanish">Spanish</option>
                    </select>
                </label>
                <label class="block mt-4 text-sm" id="write">
                    <span class="text-gray-700">
                        Writing style
                    </span>
                    <select class="block w-full mt-1 text-sm 

form-select focus:border-indigo-400 
                    focus:outline-none focus:shadow-outline-indigo 

                    id="write_style"
                    name="write_style"
                    value="{{$aicontent->write_style}}">
                        <option value="Formal and respectful">Formal and respectful</option>
                        <option value="Neutral and professional">Neutral and professional</option>
                        <option value="Casual and friendly">Casual and friendly</option>
                    </select>
                </label>
                <label class="block mt-6 text-sm hidden" id="personalized">
                    <span class="text-gray-700">
                        Personalize by
                    </span>
                    <select class="block w-full mt-1 text-sm 

form-select focus:border-indigo-400 
                    focus:outline-none focus:shadow-outline-indigo 

                    id="personalized_by"
                    name="personalized_by">
                        <option value="location">Location</option>
                        <option value="mutual_connection">Mutual Connections</option>
                        <option value="mutual_interest">Mutual Interests</option>
                        <option value="industry">Industry</option>
                        <option value="jobtitle">Job title</option>
                        <option value="random">Random</option>
                    </select>
                </label>
                <label class="block mt-8 text-sm w-full hidden location-elem">
                    <input
                    type="text"
                    class="block w-full mt-2 text-sm                   focus:border-indigo-400 focus:outline-none 
                    focus:shadow-outline-indigo form-input rounded-md border-gray-200"
                    placeholder="Enter location"
                    id="location" name="location"
                    value="{{$aicontent->connection_message_location}}"
                    />
                </label>
                <label class="block mt-8 text-sm w-full hidden industry-elem">
                    <input
                    type="text"
                    class="block w-full mt-2 text-sm                   focus:border-indigo-400 focus:outline-none 
                    focus:shadow-outline-indigo form-input rounded-md border-gray-200"
                    placeholder="Enter industry"
                    id="industry" name="industry"
                    value="{{$aicontent->connection_message_industry}}"
                    />
                </label>
                <label class="block mt-8 text-sm w-full hidden jobtitle-elem">
                    <input
                    type="text"
                    class="block w-full mt-2 text-sm                   focus:border-indigo-400 focus:outline-none 
                    focus:shadow-outline-indigo form-input rounded-md border-gray-200"
                    placeholder="Enter job title"
                    id="jobtitle" name="jobtitle"
                    value="{{$aicontent->connection_message_jobtitle}}"
                    />
                </label>
                <label class="block mt-4 text-sm" id="ideal">
                    <span class="text-gray-700">Idea </span>
                    <textarea
                    class="block w-full mt-1 text-sm form-textarea 
                    focus:border-indigo-400 focus:outline-none rounded-md
                    focus:shadow-outline-indigo
                    rows="3"
                    id="idea"
                    name="idea"
                    placeholder="Enter an idea or niche to generate content."
                    >{{$aicontent->idea}}</textarea>
                </label>
                <div class="block mt-4 text-sm">
                    <div class="mb-1 flex items-center justify-between">
                        <span class="font-medium text-gray-700">Content</span>
                        <span class="text-xs text-gray-500"><span id="contentWordCount">{{ $aicontent->word_counts ?? 0 }}</span> words</span>
                    </div>
                    <x-simple-text-editor
                        id="content"
                        name="content"
                        :value="$aicontent->contents"
                        :rows="12"
                        min-height="min-h-[280px]"
                        placeholder="Generated content appears here with line breaks. Edit, then copy or save."
                        :required="true"
                    />
                </div>
                <input type="hidden" id="words" name="words" value="{{ $aicontent->word_counts }}">
                <div class="mt-5 flex flex-wrap justify-end gap-3">
                    <button type="button" 
                    class="inline-flex w-full justify-center rounded-md 
                    bg-indigo-600 px-3 py-2 text-sm font-semibold text-white 
                    shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto" id="generate">
                        <span>Regenerate</span>
                        <span class="hidden spinner">
                            <svg aria-hidden="true" role="status" class="inline w-4 h-4 ml-2 text-white animate-spin" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="#E5E7EB"/>
                                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentColor"/>
                            </svg>
                        </span>
                    </button>
                    <button type="submit" 
                    class="mt-3 inline-flex w-full justify-center rounded-md 
                    bg-white px-3 py-2 text-sm font-semibold text-gray-900 
                    shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 
                    sm:mt-0 sm:w-auto"
                    id="save">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function syncContentWordCount() {
        const count = window.SimpleTextEditor
            ? window.SimpleTextEditor.getWordCount('content')
            : ($('#content').val().trim() ? $('#content').val().trim().split(/\s+/).length : 0);
        $('#words').val(count);
        $('#contentWordCount').text(count);
    }

    $('#content').on('input simple-text-editor:input', syncContentWordCount);

    $(document).ready(function() {
        if (window.SimpleTextEditor) {
            window.SimpleTextEditor.init();
        }
        syncContentWordCount();
    });

    $('#cold-mail').click(function() {
        $('#write').show()
        $('#personalized').hide()
        $('#ideal').show()
        toogleSection('first_cold_email','#cold-mail')
    })
    
    $('#linkedin').click(function() {
        $('#write').hide()
        $('#personalized').show()
        $('#ideal').hide()
        toogleSection('linkedin_connection_message','#linkedin')
    })
    
    $('#ice-breaker').click(function() {
        $('#write').hide()
        $('#personalized').hide()
        $('#ideal').hide()
        toogleSection('personalized_ice_breaker','#ice-breaker')
    })
    
    $('#personalized_by').change(function() {
        if($(this).val() == 'random') {
            $('#ideal').show()
            $('.location-elem').hide()
            $('.industry-elem').hide()
            $('.jobtitle-elem').hide()
        }else if($(this).val() == 'location') {
            $('.location-elem').show()
            $('.industry-elem').hide()
            $('.jobtitle-elem').hide()
            $('#ideal').hide()
        }else if($(this).val() == 'industry') {
            $('.industry-elem').show()
            $('.location-elem').hide()
            $('.jobtitle-elem').hide()
            $('#ideal').hide()
        }else if($(this).val() == 'jobtitle') {
            $('.jobtitle-elem').show()
            $('.location-elem').hide()
            $('.industry-elem').hide()
            $('#ideal').hide()
        }else {
            $('.jobtitle-elem').hide()
            $('.location-elem').hide()
            $('.industry-elem').hide()
            $('#ideal').hide()
        }
    })
    
    $('#generate').click(function() {
        if(validator() == false) return;
        $(this).attr('disabled',true);
        $('#save').attr('disabled',true);
        $('.spinner').show();
    
        $.ajax({
            method: 'post',
            beforeSend: function(request) {
                request.setRequestHeader('Content-Type', 'application/json')
            },
            headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}"},
            url: '/ai-content/generate',
            data: JSON.stringify({
                'language': $('#language').val(),
                'aitype': $('#aitype').val(),
                'idea': $('#idea').val(),
                'write_style': $('#write_style').val(),
                'personalized_by': $('#personalized_by').val(),
                'location': $('#location').val(),
                'industry': $('#industry').val(),
                'jobtitle': $('#jobtitle').val(),
            }),
            success: function(res) {
                $('#generate').attr('disabled',false)
                $('#save').attr('disabled',false)
                if (window.SimpleTextEditor) {
                    window.SimpleTextEditor.setValue('content', res.content || '');
                } else {
                    $('#content').val(res.content)
                }
                $('#words').val(res.words)
                syncContentWordCount()
                $('.spinner').hide()
            },
            error: function(err, status, error) {
                $('#generate').attr('disabled',false);
                $('#save').attr('disabled',false);
                $('.spinner').hide()
                err = eval("(" + err.responseText + ")");
                displayError(err.Message)
            }
        })
    })
    
    const toogleSection = (aitype, elemId) => {
        $('.section-btn').removeClass('text-indigo-600')
        $(elemId).addClass('text-indigo-600')
        $('#aitype').val(aitype)
    
        if(aitype === 'linkedin_connection_message') {
            $('.location-elem').show()
        }else {
            $('.jobtitle-elem').hide()
            $('.location-elem').hide()
            $('.industry-elem').hide()
        }
    }
    
    const validator = () => {
        let aitype = $('#aitype').val(),
            idea = $('#idea').val(),
            personalized = $('#personalized_by').val();
    
        if(aitype == 'first_cold_email' && idea == '') {
            displayError('Idea field is required.')
            $('#generate').attr('disabled',false);
            $('#save').attr('disabled',false);
            return false;
        }
    
        if(aitype == 'linkedin_connection_message') {
            if(personalized == 'location' && !$('#location').val()) {
                displayError('Location field is required.')
                $('#generate').attr('disabled',false);
                $('#save').attr('disabled',false);
                return false;
            }else if(personalized == 'industry' && !$('#industry').val()) {
                displayError('Industry field is required.')
                $('#generate').attr('disabled',false);
                $('#save').attr('disabled',false);
                return false;
            }else if(personalized == 'jobtitle' && !$('#jobtitle').val()) {
                displayError('Job title field is required.')
                $('#generate').attr('disabled',false);
                $('#save').attr('disabled',false);
                return false;
            }else if(personalized == 'random' && !$('#idea').val()) {
                displayError('Idea field is required.')
                $('#generate').attr('disabled',false);
                $('#save').attr('disabled',false);
                return false;
            }
        }
    }
    
    const displayError = message => {
        $('#notification').show()
        $('.notify-message').html(message)
        setTimeout(() => {
            $('#notification').hide()
        },4000)
        window.scrollTo(0, 0);
    }

    const onMounted = () => {
        if($('#aitype').val() == 'linkedin_connection_message') {
            $('#linkedin').addClass('text-indigo-600')
            $('#cold-mail').removeClass('text-indigo-600')
        }else if($('#aitype').val() == 'personalized_ice_breaker') {
            $('#ice-breaker').addClass('text-indigo-600')
            $('#cold-mail').removeClass('text-indigo-600')
        }
    
    }

    onMounted();
</script>
@endsection
