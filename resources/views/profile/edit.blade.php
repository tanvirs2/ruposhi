@extends('layouts.app')
@section('title', 'আমার প্রোফাইল')
@section('page-title', 'আমার প্রোফাইল')

@section('content')
<div style="max-width:600px;margin:0 auto;display:flex;flex-direction:column;gap:20px">

    {{-- Profile Info Card --}}
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-user-circle" style="color:var(--accent)"></i> ব্যক্তিগত তথ্য</h3>
        </div>
        <div style="padding:24px">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div class="form-group-field" style="margin-bottom:16px">
                    <label>নাম <span class="req">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                           class="@error('name') is-invalid @enderror" required>
                    @error('name')
                        <span style="color:#dc2626;font-size:.8rem">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group-field" style="margin-bottom:20px">
                    <label>ইমেইল <span class="req">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="@error('email') is-invalid @enderror" required>
                    @error('email')
                        <span style="color:#dc2626;font-size:.8rem">{{ $message }}</span>
                    @enderror
                </div>

                <div style="display:flex;align-items:center;gap:12px">
                    <div style="width:52px;height:52px;border-radius:50%;
                                background:var(--accent);color:#fff;
                                display:flex;align-items:center;justify-content:center;
                                font-size:1.4rem;font-weight:700;flex-shrink:0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight:600;color:var(--text-primary)">{{ $user->name }}</div>
                        <div style="font-size:.82rem;color:var(--text-secondary)">
                            {{ $user->role === 'admin' ? 'অ্যাডমিন' : 'স্টাফ' }}
                        </div>
                    </div>
                </div>

                <hr style="border:none;border-top:1px solid var(--border);margin:20px 0">

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> তথ্য সংরক্ষণ করুন
                </button>
            </form>
        </div>
    </div>

    {{-- Change Password Card --}}
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-lock" style="color:#d97706"></i> পাসওয়ার্ড পরিবর্তন</h3>
        </div>
        <div style="padding:24px">
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')

                <div class="form-group-field" style="margin-bottom:16px">
                    <label>বর্তমান পাসওয়ার্ড <span class="req">*</span></label>
                    <div style="position:relative">
                        <input type="password" name="current_password" id="cur_pw"
                               placeholder="বর্তমান পাসওয়ার্ড লিখুন" required
                               style="padding-right:42px;width:100%">
                        <button type="button" onclick="togglePw('cur_pw','cur_eye')"
                                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                                       background:none;border:none;cursor:pointer;color:var(--text-secondary)">
                            <i class="fas fa-eye" id="cur_eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group-field" style="margin-bottom:16px">
                    <label>নতুন পাসওয়ার্ড <span class="req">*</span></label>
                    <div style="position:relative">
                        <input type="password" name="new_password" id="new_pw"
                               placeholder="কমপক্ষে ৬ অক্ষর" required minlength="6"
                               style="padding-right:42px;width:100%"
                               oninput="checkStrength(this.value)">
                        <button type="button" onclick="togglePw('new_pw','new_eye')"
                                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                                       background:none;border:none;cursor:pointer;color:var(--text-secondary)">
                            <i class="fas fa-eye" id="new_eye"></i>
                        </button>
                    </div>
                    {{-- Strength bar --}}
                    <div style="margin-top:6px;height:4px;border-radius:4px;background:var(--border);overflow:hidden">
                        <div id="strengthBar" style="height:100%;width:0;transition:width .3s,background .3s;border-radius:4px"></div>
                    </div>
                    <span id="strengthText" style="font-size:.75rem;color:var(--text-secondary)"></span>
                </div>

                <div class="form-group-field" style="margin-bottom:20px">
                    <label>নতুন পাসওয়ার্ড নিশ্চিত করুন <span class="req">*</span></label>
                    <div style="position:relative">
                        <input type="password" name="new_password_confirmation" id="con_pw"
                               placeholder="আবার লিখুন" required
                               style="padding-right:42px;width:100%">
                        <button type="button" onclick="togglePw('con_pw','con_eye')"
                                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                                       background:none;border:none;cursor:pointer;color:var(--text-secondary)">
                            <i class="fas fa-eye" id="con_eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn" style="background:#d97706;color:#fff">
                    <i class="fas fa-key"></i> পাসওয়ার্ড পরিবর্তন করুন
                </button>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function togglePw(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

function checkStrength(val) {
    const bar  = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { w: '20%', bg: '#dc2626', label: 'খুব দুর্বল' },
        { w: '40%', bg: '#f97316', label: 'দুর্বল' },
        { w: '60%', bg: '#eab308', label: 'মধ্যম' },
        { w: '80%', bg: '#22c55e', label: 'শক্তিশালী' },
        { w: '100%', bg: '#16a34a', label: 'খুব শক্তিশালী' },
    ];
    const l = levels[Math.min(score, 4)];
    bar.style.width      = val ? l.w : '0';
    bar.style.background = l.bg;
    text.textContent     = val ? l.label : '';
    text.style.color     = l.bg;
}
</script>
@endpush
