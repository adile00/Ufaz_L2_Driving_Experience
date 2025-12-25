<?php
// Maps random tokens -> expID in $_SESSION (anonymises PK in URLs)

function token_for_exp(int $expID): string {
    if (!isset($_SESSION['exp_tokens'])) $_SESSION['exp_tokens'] = [];

    foreach ($_SESSION['exp_tokens'] as $tok => $id) {
        if ((int)$id === $expID) return $tok;
    }

    $token = bin2hex(random_bytes(8));
    $_SESSION['exp_tokens'][$token] = $expID;
    return $token;
}

function exp_from_token(string $token): ?int {
    return isset($_SESSION['exp_tokens'][$token]) ? (int)$_SESSION['exp_tokens'][$token] : null;
}
