@extends('layouts.app')

@section('title', 'Evidence Management')

@section('content')
<div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">All Supporting Evidence</h1>

    <div class="space-y-4">
        @foreach($evidence as $item)
            <div class="rounded-lg border p-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    @if($item->file_type === 'image')
                        <img src="{{ asset('storage/'.$item->file_path) }}" alt="thumb" style="height:64px; width:96px; object-fit:cover; border-radius:8px;"/>
                    @else
                        <div style="height:64px; width:96px; display:flex; align-items:center; justify-content:center; background:#f3f4f6; border-radius:8px;">PDF</div>
                    @endif
                    <div>
                        <div class="font-semibold">{{ $item->file_name }}</div>
                        <div class="text-sm text-slate-500">Uploaded by: {{ $item->user?->name ?? 'Guest' }} • {{ $item->created_at->diffForHumans() }}</div>
                        <div class="text-sm text-slate-500">Feedback: <a href="{{ route('feedback.show', $item->feedback) }}">{{ $item->feedback->title }}</a></div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.evidence.download', $item) }}" class="btn-next">Download</a>
                    <form method="POST" action="{{ route('admin.evidence.destroy', $item) }}" onsubmit="return confirm('Delete this evidence?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-back">Delete</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $evidence->links() }}
    </div>
</div>

@endsection
