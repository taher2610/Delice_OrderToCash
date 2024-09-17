<?php

namespace App\DTO;

class RegistrationDTO
{
    private string $email;
    private string $plainPassword;
    private string $plainPasswordConfirmation;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function getPlainPasswordConfirmation(): string
    {
        return $this->plainPasswordConfirmation;
    }

    public function setPlainPasswordConfirmation(string $plainPasswordConfirmation): void
    {
        $this->plainPasswordConfirmation = $plainPasswordConfirmation;
    }

    public function isPasswordConfirmationValid(): bool
    {
        return $this->plainPassword === $this->plainPasswordConfirmation;
    }
}
