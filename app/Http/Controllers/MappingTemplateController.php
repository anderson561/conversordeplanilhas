<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\MappingTemplate;
use Illuminate\Support\Facades\Auth;

class MappingTemplateController extends Controller
{
    public function index()
    {
        return MappingTemplate::where('user_id', Auth::id())
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'mapping_rules' => 'required|array',
        ]);

        $template = MappingTemplate::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'mapping_rules' => $request->mapping_rules,
        ]);

        return response()->json($template, 201);
    }

    public function destroy(MappingTemplate $template)
    {
        if ($template->user_id !== Auth::id()) {
            abort(403);
        }

        $template->delete();

        return response()->noContent();
    }
}
