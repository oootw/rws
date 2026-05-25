<?php

test('приложение возвращает успешный ответ', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
