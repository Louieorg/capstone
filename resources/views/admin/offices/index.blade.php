@extends('layouts.admin')

@section('title', 'Office Directory')
@section('subtitle', 'The school\'s actual offices, each with one official representative')

@section('content')

<style>
.of-shell { display: flex; flex-direction: column; gap: 18px; }

.of-panel {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 14px; padding: 18px 20px;
}
.of-panel-title {
  font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700;
  color: var(--text); margin-bottom: 4px;
}
.of-panel-sub { font-size: 11.5px; color: var(--muted2); line-height: 1.6; margin-bottom: 16px; }

.of-grid {
  display: grid; gap: 12px;
  grid-template-columns: 1fr 1fr 1fr auto;
  align-items: end;
}
@media (max-width: 900px) {
  .of-grid { grid-template-columns: 1fr; }
}
.of-field label {
  display: block; font-size: 12px; font-weight: 600;
  color: var(--text2); margin-bottom: 5px;
}
.of-field label span { font-weight: 400; color: var(--muted2); }
.of-input {
  width: 100%; background: var(--surface2);
  border: 1px solid var(--border); border-radius: 9px;
  padding: 8px 12px; font-size: 13px; color: var(--text);
  font-family: 'DM Sans', sans-serif;
}
.of-input:focus { outline: none; border-color: var(--amber); box-shadow: 0 0 0 3px var(--amber-dim); }
.of-error { margin-top: 4px; font-size: 11.5px; color: var(--red); }

.of-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  padding: 8px 16px; border-radius: 9px;
  font-size: 12.5px; font-weight: 600; text-decoration: none;
  font-family: 'DM Sans', sans-serif; cursor: pointer;
  white-space: nowrap; transition: filter .15s, background .15s;
}
/* Primary — the one committed change on the form it belongs to. */
.of-btn-primary { background: var(--amber); color: #0a0b0f; border: 1px solid var(--amber); }
.of-btn-primary:hover { filter: brightness(1.08); }
/* Secondary — routine edits. */
.of-btn-secondary { background: transparent; color: var(--text2); border: 1px solid var(--border); }
.of-btn-secondary:hover { border-color: var(--amber-mid); color: var(--text); background: var(--amber-dim); }
/* Destructive / state-changing — takes the office out of circulation. */
.of-btn-danger { background: transparent; color: var(--red); border: 1px solid var(--red-b); }
.of-btn-danger:hover { background: var(--red-bg); }
.of-btn-resume { background: transparent; color: var(--green); border: 1px solid var(--green-b); }
.of-btn-resume:hover { background: var(--green-bg); }

.of-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 14px; padding: 18px 20px;
  transition: border-color .15s;
}
.of-card:hover { border-color: var(--amber-mid); }
.of-card.is-inactive { border-style: dashed; }
.of-card-head {
  display: flex; flex-wrap: wrap; align-items: center;
  justify-content: space-between; gap: 10px; margin-bottom: 14px;
}
.of-name { font-family: 'Sora', sans-serif; font-size: 14.5px; font-weight: 700; color: var(--text); }
.of-state {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 10px; border-radius: 999px;
  font-size: 11px; font-weight: 600; border: 1px solid;
}
.of-state-active { background: var(--green-bg); color: var(--green); border-color: var(--green-b); }
.of-state-inactive { background: var(--surface2); color: var(--muted2); border-color: var(--border); }

.of-meta {
  display: flex; flex-wrap: wrap; align-items: center; gap: 6px 18px;
  margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--border);
  font-size: 11.5px; color: var(--muted2);
}
.of-meta strong { color: var(--text); font-weight: 600; }
.of-meta a { color: var(--amber); text-decoration: none; }
.of-meta a:hover { text-decoration: underline; }
.of-meta-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-left: auto; }

.empty-state {
  padding: 40px; text-align: center;
  background: var(--surface); border: 1px dashed var(--border); border-radius: 14px;
}
.empty-text { font-size: 13px; color: var(--muted2); }
</style>

<div class="of-shell mx-auto max-w-7xl">

  <section class="of-panel" aria-label="Add an office">
    <div class="of-panel-title">Add Office</div>
    <p class="of-panel-sub">
      An office is a real unit of the school. It has exactly one official representative and an
      optional official contact email. Reviewer roles and category assignments are managed separately.
    </p>

    <form method="POST" action="{{ route('admin.offices.store') }}" class="of-grid">
      @csrf
      <div class="of-field">
        <label for="new-office-name">Office name</label>
        <input id="new-office-name" name="name" value="{{ old('name') }}" required maxlength="255" class="of-input">
        @error('name')<p class="of-error">{{ $message }}</p>@enderror
      </div>
      <div class="of-field">
        <label for="new-office-representative">Office representative</label>
        <select id="new-office-representative" name="representative_user_id" required class="of-input">
          <option value="">Select a user</option>
          @foreach($users as $user)
            <option value="{{ $user->id }}" @selected((string) old('representative_user_id') === (string) $user->id)>{{ $user->name }}</option>
          @endforeach
        </select>
        @error('representative_user_id')<p class="of-error">{{ $message }}</p>@enderror
      </div>
      <div class="of-field">
        <label for="new-office-contact-email">Official contact email <span>(optional)</span></label>
        <input id="new-office-contact-email" type="email" name="contact_email" value="{{ old('contact_email') }}" maxlength="255" class="of-input">
        @error('contact_email')<p class="of-error">{{ $message }}</p>@enderror
      </div>
      <div class="of-field">
        <button type="submit" class="of-btn of-btn-primary">Add Office</button>
      </div>
    </form>
  </section>

  <div style="display:flex;flex-direction:column;gap:12px">
    @forelse($offices as $office)
      <article class="of-card {{ $office->is_active ? '' : 'is-inactive' }}">
        <div class="of-card-head">
          <h2 class="of-name">{{ $office->name }}</h2>
          <span class="of-state {{ $office->is_active ? 'of-state-active' : 'of-state-inactive' }}">
            {{ $office->is_active ? 'Active' : 'Inactive' }}
          </span>
        </div>

        <form method="POST" action="{{ route('admin.offices.update', $office) }}" class="of-grid">
          @csrf
          @method('PATCH')
          <div class="of-field">
            <label for="office-name-{{ $office->id }}">Office name</label>
            <input id="office-name-{{ $office->id }}" name="name" value="{{ $office->name }}" required maxlength="255" class="of-input">
            @error('name')<p class="of-error">{{ $message }}</p>@enderror
          </div>
          <div class="of-field">
            <label for="office-representative-{{ $office->id }}">Office representative</label>
            <select id="office-representative-{{ $office->id }}" name="representative_user_id" required class="of-input">
              @foreach($users as $user)
                <option value="{{ $user->id }}" @selected($office->representative_user_id === $user->id)>{{ $user->name }}</option>
              @endforeach
            </select>
            @error('representative_user_id')<p class="of-error">{{ $message }}</p>@enderror
          </div>
          <div class="of-field">
            <label for="office-contact-email-{{ $office->id }}">Official contact email <span>(optional)</span></label>
            <input id="office-contact-email-{{ $office->id }}" type="email" name="contact_email" value="{{ $office->contact_email }}" maxlength="255" class="of-input">
            @error('contact_email')<p class="of-error">{{ $message }}</p>@enderror
          </div>
          <div class="of-field">
            <button type="submit" class="of-btn of-btn-secondary">Save changes</button>
          </div>
        </form>

        <div class="of-meta">
          <span>Office representative: <strong>{{ $office->representative->name }}</strong></span>
          @if($office->contact_email)
            <span>Official contact: <a href="mailto:{{ $office->contact_email }}">{{ $office->contact_email }}</a></span>
          @else
            <span>Official contact: <strong>Not provided</strong></span>
          @endif
          <div class="of-meta-actions">
            <form method="POST" action="{{ route('admin.offices.status', $office) }}">
              @csrf
              @method('PATCH')
              <input type="hidden" name="is_active" value="{{ $office->is_active ? '0' : '1' }}">
              <button type="submit" class="of-btn {{ $office->is_active ? 'of-btn-danger' : 'of-btn-resume' }}">
                {{ $office->is_active ? 'Deactivate' : 'Reactivate' }}
              </button>
            </form>
          </div>
        </div>
      </article>
    @empty
      <div class="empty-state">
        <p class="empty-text">No offices have been added yet.</p>
      </div>
    @endforelse
  </div>

</div>

@endsection
