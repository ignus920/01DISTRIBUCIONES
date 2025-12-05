<?php

namespace App\Livewire\Profile;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;

class UpdateAvatarForm extends Component
{
    use WithFileUploads;

    #[Validate('nullable|image|max:2048')] // 2MB max
    public $avatar;

    public $currentAvatar;

    public function mount()
    {
        $this->currentAvatar = Auth::user()->avatar;
    }

    public function save()
    {
        $this->validate();

        $user = Auth::user();

        if ($this->avatar) {
            // Eliminar avatar anterior si existe
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Guardar nueva imagen
            $path = $this->avatar->store('avatars', 'public');

            $user->update(['avatar' => $path]);
            $this->currentAvatar = $path;

            session()->flash('avatar-message', 'Avatar actualizado exitosamente.');
            $this->redirect(request()->header('Referer'), navigate: true);
        }

        $this->reset('avatar');
    }

    public function deleteAvatar()
    {
        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);
        $this->currentAvatar = null;

        session()->flash('avatar-message', 'Avatar eliminado exitosamente.');
        $this->redirect(request()->header('Referer'), navigate: true);
    }

    public function render()
    {
        return view('livewire.profile.update-avatar-form');
    }
}
