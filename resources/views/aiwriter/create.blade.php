@extends('layout.auth')

@section('content')
<h2 class="text-lg font-semibold text-gray-700 mb-6">
    New AI Content
</h2>
<div class="mb-8">
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-3">Select Content Type</label>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="section-btn-card p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all hover:border-[#0077b5] hover:shadow-md bg-white group" id="cold-mail-card">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-400 group-hover:text-[#0077b5] transition-colors">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="section-btn font-semibold text-gray-700 group-hover:text-[#0077b5] transition-colors" id="cold-mail">
                    First cold email
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Create professional first-contact emails</p>
                    </div>
                </div>
            </div>
            <div class="section-btn-card p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all hover:border-[#0077b5] hover:shadow-md bg-white group" id="linkedin-card">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24" class="w-6 h-6 text-gray-400 group-hover:text-[#0077b5] transition-colors">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="section-btn font-semibold text-gray-700 group-hover:text-[#0077b5] transition-colors" id="linkedin">
                    LinkedIn connection message
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Personalized LinkedIn connection requests</p>
                    </div>
                </div>
            </div>
            <div class="section-btn-card p-4 border-2 border-gray-200 rounded-lg cursor-pointer transition-all hover:border-[#0077b5] hover:shadow-md bg-white group" id="ice-breaker-card">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-400 group-hover:text-[#0077b5] transition-colors">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="section-btn font-semibold text-gray-700 group-hover:text-[#0077b5] transition-colors" id="ice-breaker">
                    Personalized ice-breaker
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Engaging conversation starters</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="md:flex gap-6 mb-8">
    <div class="w-full rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="w-full">
            <div class="p-4 mb-4 mt-4 text-sm text-red-800 rounded-lg 
            bg-red-100 hidden"
            id="notification" role="alert">
                <span class="font-medium">Error!</span>
                <span class="notify-message"></span>
            </div>
            <form action="{{route('aiwriter.store')}}" method="post">
                @csrf
                <input type="hidden" id="aitype" name="aitype" value="first_cold_email">
                <label class="block text-sm w-full">
                    <span class="text-gray-700">Title</span>
                    <input
                    type="text"
                    class="block w-full mt-1 text-sm focus:border-[#0077b5] focus:outline-none 
                    focus:shadow-outline-[#0077b5] form-input rounded-md border-gray-200"
                    placeholder="Content title"
                    id="title" name="title"
                    value="{{old('title')}}"
                    required
                    />
                </label>
                <label class="block mt-4 mb-4 text-sm">
                    <span class="text-gray-700">
                        Language
                    </span>
                    <select class="block w-full mt-1 text-sm 
                    form-select rounded-md
                    focus:border-[#0077b5] focus:outline-none 
                    focus:shadow-outline-[#0077b5]"
                    id="language" name="language" value="{{old('language')}}">
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
                    form-select rounded-md focus:border-[#0077b5] 
                    focus:outline-none focus:shadow-outline-[#0077b5]"
                    id="write_style"
                    name="write_style">
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
                    form-select focus:border-[#0077b5] 
                    focus:outline-none focus:shadow-outline-[#0077b5] rounded-md"
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
                    class="block w-full mt-2 text-sm focus:border-[#0077b5] focus:outline-none 
                    focus:shadow-outline-[#0077b5] mt-3
                    form-input rounded-md border-gray-200"
                    placeholder="Enter location"
                    id="location" name="location"
                    value="{{old('location')}}"
                    />
                </label>
                <label class="block mt-8 text-sm w-full hidden industry-elem">
                    <input
                    type="text"
                    class="block w-full mt-2 text-sm focus:border-[#0077b5] focus:outline-none 
                    focus:shadow-outline-[#0077b5] 
                    form-input rounded-md border-gray-200"
                    placeholder="Enter industry"
                    id="industry" name="industry"
                    value="{{old('industry')}}"
                    />
                </label>
                <label class="block mt-8 text-sm w-full hidden jobtitle-elem">
                    <input
                    type="text"
                    class="block w-full mt-2 text-sm focus:border-[#0077b5] focus:outline-none 
                    focus:shadow-outline-[#0077b5] 
                    form-input rounded-md border-gray-200"
                    placeholder="Enter job title"
                    id="jobtitle" name="jobtitle"
                    value="{{old('jobtitle')}}"
                    />
                </label>
                <label class="block mt-4 text-sm" id="ideal">
                    <span class="text-gray-700">Idea </span>
                    <textarea
                    class="block w-full mt-1 text-sm form-textarea 
                    focus:border-[#0077b5] focus:outline-none rounded-md
                    focus:shadow-outline-[#0077b5]"
                    rows="3"
                    id="idea"
                    name="idea"
                    placeholder="Enter an idea or niche to generate content."
                    value="{{old('idea')}}"
                    ></textarea>
                </label>
                <div class="block mt-4 text-sm">
                    <div class="mb-1 flex items-center justify-between">
                        <span class="font-medium text-gray-700">Content</span>
                        <span class="text-xs text-gray-500"><span id="contentWordCount">0</span> words</span>
                    </div>
                    <x-simple-text-editor
                        id="content"
                        name="content"
                        :value="old('content', '')"
                        :rows="12"
                        min-height="min-h-[280px]"
                        placeholder="Generated content appears here with line breaks. Edit, then copy or save."
                        :required="true"
                    />
                </div>
                <input type="hidden" id="words" name="words" value="{{ old('words', 0) }}">
                <div class="mt-5 flex flex-wrap justify-end gap-3">
                    <button type="button" 
                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all"
                    style="background: linear-gradient(135deg, #0077b5 0%, #005885 100%);" onmouseover="this.style.background='linear-gradient(135deg, #005885 0%, #004d6f 100%)'; this.style.boxShadow='0 4px 12px rgba(0, 119, 181, 0.3)';" onmouseout="this.style.background='linear-gradient(135deg, #0077b5 0%, #005885 100%)'; this.style.boxShadow='none';"
                    id="generate">
                        <span>Generate</span>
                        <span class="hidden spinner">
                            <svg aria-hidden="true" role="status" class="inline w-4 h-4 ml-2 text-white animate-spin " viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="#E5E7EB"/>
                                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentColor"/>
                            </svg>
                        </span>
                    </button>
                    <button type="submit" 
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
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

$('#cold-mail, #cold-mail-card').click(function() {
    $('#write').show()
    $('#personalized').hide()
    $('#ideal').show()
    toogleSection('first_cold_email','#cold-mail')
})

$('#linkedin, #linkedin-card').click(function() {
    $('#write').hide()
    $('#personalized').show()
    $('#ideal').hide()
    toogleSection('linkedin_connection_message','#linkedin')
})

$('#ice-breaker, #ice-breaker-card').click(function() {
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
    // Remove active state from all cards and buttons
    $('.section-btn').removeClass('text-[#0077b5]')
    $('.section-btn-card').removeClass('border-[#0077b5] bg-blue-50').addClass('border-gray-200')
    
    // Add active state to selected card and button
    $(elemId).addClass('text-[#0077b5]')
    
    // Find and highlight the corresponding card
    if(elemId === '#cold-mail') {
        $('#cold-mail-card').removeClass('border-gray-200').addClass('border-[#0077b5] bg-blue-50')
    } else if(elemId === '#linkedin') {
        $('#linkedin-card').removeClass('border-gray-200').addClass('border-[#0077b5] bg-blue-50')
    } else if(elemId === '#ice-breaker') {
        $('#ice-breaker-card').removeClass('border-gray-200').addClass('border-[#0077b5] bg-blue-50')
    }
    
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

// Initialize first card as selected on page load
$(document).ready(function() {
    $('#cold-mail-card').removeClass('border-gray-200').addClass('border-[#0077b5] bg-blue-50')
    if (window.SimpleTextEditor) {
        window.SimpleTextEditor.init();
    }
    syncContentWordCount()
})
</script>
@endsection