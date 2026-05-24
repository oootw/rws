<?php

declare(strict_types=1);

namespace App\Interface\Http\Requests\Public;

use App\Application\Reviews\SubmitReview\SubmitReviewCommand;
use App\Contracts\Captcha\CaptchaVerifierInterface;
use App\Domain\Places\Place;
use App\Support\IpHasher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Валидирует входящий JSON формы отзыва и собирает Application-команду.
 *
 * HTTP-слой не лезет в домен напрямую: контроллер получает готовый Command,
 * домен ничего не знает про FormRequest, IP, капчу.
 */
final class SubmitReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'stars' => ['required', 'integer', 'min:1', 'max:3'],
            'text' => ['required', 'string', 'max:5000'],
            'contact' => ['required', 'string', 'max:255'],
            'consent_accepted' => ['accepted'],
            'captcha_token' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stars.required' => 'Укажите оценку.',
            'stars.integer' => 'Оценка должна быть числом.',
            'stars.min' => 'Негативный отзыв возможен только при оценке от 1 до 3 звёзд.',
            'stars.max' => 'Негативный отзыв возможен только при оценке от 1 до 3 звёзд.',
            'text.required' => 'Напишите текст отзыва.',
            'text.max' => 'Текст отзыва не должен превышать 5000 символов.',
            'contact.required' => 'Укажите контакт для связи.',
            'contact.max' => 'Контакт не должен превышать 255 символов.',
            'consent_accepted.accepted' => 'Необходимо согласие на обработку персональных данных.',
            'captcha_token.required' => 'Подтвердите, что вы не робот.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'stars' => 'оценка',
            'text' => 'текст отзыва',
            'contact' => 'контакт',
            'consent_accepted' => 'согласие',
            'captcha_token' => 'капча',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $token = $this->string('captcha_token')->toString();
            $verifier = app(CaptchaVerifierInterface::class);

            if (! $verifier->verify($token, $this->ip())) {
                $validator->errors()->add('captcha_token', 'Не удалось пройти проверку капчи.');
            }
        });
    }

    public function toCommand(Place $place): SubmitReviewCommand
    {
        return new SubmitReviewCommand(
            placeId: $place->id->value,
            stars: $this->integer('stars'),
            text: $this->string('text')->toString(),
            contact: $this->string('contact')->toString(),
            ipHash: IpHasher::hash($this->ip()),
        );
    }
}
