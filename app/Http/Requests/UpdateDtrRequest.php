<?php

namespace App\Http\Requests;

use App\Models\DtrEntry;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateDtrRequest extends StoreDtrRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $dtr = $this->route('dtr');

        if ($dtr instanceof DtrEntry && $dtr->user_id !== $this->user()?->id) {
            abort(404);
        }

        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var DtrEntry $dtr */
        $dtr = $this->route('dtr');

        return self::rulesFor((int) $this->user()->id, $dtr);
    }
}
