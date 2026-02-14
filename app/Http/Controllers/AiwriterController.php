<?php

namespace App\Http\Controllers;

use App\Models\AiContent;
use App\Services\ChatGPT;
use App\Helpers\CampaignHelper;
use Illuminate\Http\Request;

class AiwriterController extends Controller
{
    use CampaignHelper;
    
    public function index(Request $request)
    {
        $title = $request->query('title');

        $aicontent = new AiContent;

        if(isset($title)){
            $aicontents = $aicontent->where('title','like','%'.$title.'%')
                ->where('user_id', auth()->user()->id)
                ->latest()
                ->paginate(20);
        }else {
            $aicontents = $aicontent->where('user_id', auth()->user()->id)
                ->latest()
                ->paginate(20);
        }

        return view('aiwriter.index', compact('aicontents'));
    }

    public function create()
    {
        return view('aiwriter.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required'],
            'aitype' => ['required'],
            'content' => ['required']
        ]);

        AiContent::create([
            'title' => $request->title,
            'ai_type' => $request->aitype,
            'language' => $request->language,
            'idea' => $request->idea,
            'write_style' => $request->write_style,
            'connection_message_type' => $request->personalized_by,
            'connection_message_location' => $request->location,
            'connection_message_industry' => $request->industry,
            'connection_message_jobtitle' => $request->jobtitle,
            'contents' => $request->content,
            'word_counts' => $request->words,
            'user_id' => auth()->user()->id
        ]);

        notify()->success('Content saved successfully');
        return redirect()->route('aiwriter.index');
    }

    public function edit(string $id)
    {
        $aicontent = AiContent::findOrFail($id);

        return view('aiwriter.edit', compact('aicontent'));
    }

    public function update(Request $request, string $id)
    {
        $aicontent = AiContent::findOrFail($id);

        $request->validate([
            'title' => ['required'],
            'aitype' => ['required'],
            'content' => ['required']
        ]);

        $aicontent->update([
            'title' => $request->title,
            'ai_type' => $request->aitype,
            'language' => $request->language,
            'idea' => $request->idea,
            'write_style' => $request->write_style,
            'connection_message_type' => $request->personalized_by,
            'connection_message_location' => $request->location,
            'connection_message_industry' => $request->industry,
            'connection_message_jobtitle' => $request->jobtitle,
            'contents' => $request->content,
            'word_counts' => $request->words,
        ]);

        notify()->success('Content updated successfully');
        return redirect()->route('aiwriter.index');
    }

    public function destroy(string $id)
    {
        $aicontent = AiContent::findOrFail($id);

        $aicontent->delete();

        notify()->success('Content deleted successfully');
        return redirect()->route('aiwriter.index');
    }

    public function generate(Request $request)
    {
        $data = [
            'language' => $request->language,
            'aitype' => $request->aitype,
            'idea' => $request->idea,
            'write_style' => $request->write_style,
            'connection_message_type' => $request->personalized_by,
            'location' => $request->location,
            'industry' => $request->industry,
            'jobtitle' => $request->jobtitle,
        ];

        try {
            $gpt = new ChatGPT($data);
            $result = $gpt->generate();
            if (is_array($result) && isset($result['content'])) {
                $formatted = $gpt->formatAiwriterContent($result['content'], $request->aitype);
                $result['content'] = $formatted;
                $result['words'] = str_word_count($formatted);
            }

            return response()->json($result);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ],422);
        }
    }

    public function aicontents(Request $request)
    {
        try {
            $user = $this->checkAuthorization($request);
        } catch (\Throwable $th) {
            return response()->json([
                "message" => $th->getMessage(),
                "status" => 400
            ],400);
        }

        $data = AiContent::select('title', 'contents')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $data
        ]);
    }
}
