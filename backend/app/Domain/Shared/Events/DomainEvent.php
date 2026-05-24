<?php

declare(strict_types=1);

namespace App\Domain\Shared\Events;

/**
 * Маркерный интерфейс для всех доменных событий.
 * Реализации — иммутабельные DTO в прошедшем времени (NegativeReviewSubmitted и т.п.).
 */
interface DomainEvent {}
