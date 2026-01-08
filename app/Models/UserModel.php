<?php

namespace App\Models;

class UserModel
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = WRITEPATH . 'users.json';
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }
    }

    public function getUsers(): array
    {
        return json_decode(file_get_contents($this->filePath), true) ?? [];
    }

    public function getUser(string $username): ?array
    {
        $users = $this->getUsers();
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                return $user;
            }
        }
        return null;
    }

    public function verifyUser(string $username, string $password): ?array
    {
        $user = $this->getUser($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return null;
    }

    public function saveUsers(array $users): void
    {
        file_put_contents($this->filePath, json_encode($users, JSON_PRETTY_PRINT));
    }

    public function addUser(string $username, string $password, string $role = 'user', string $homeDir = '/'): bool
    {
        if ($this->getUser($username)) {
            return false; // User exists
        }
        $users = $this->getUsers();
        $users[] = [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'home_dir' => $homeDir
        ];
        $this->saveUsers($users);
        return true;
    }

    public function changePassword(string $username, string $newPassword): bool
    {
        $users = $this->getUsers();
        foreach ($users as &$user) {
            if ($user['username'] === $username) {
                $user['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
                $this->saveUsers($users);
                return true;
            }
        }
        return false;
    }

    public function updateUser(string $username, array $data): bool
    {
        $users = $this->getUsers();
        foreach ($users as &$user) {
            if ($user['username'] === $username) {
                if (isset($data['role'])) $user['role'] = $data['role'];
                if (isset($data['home_dir'])) $user['home_dir'] = $data['home_dir'];
                if (!empty($data['password'])) {
                    $user['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
                $this->saveUsers($users);
                return true;
            }
        }
        return false;
    }

    public function deleteUser(string $username): bool
    {
        $users = $this->getUsers();
        $newUsers = array_filter($users, function ($u) use ($username) {
            return $u['username'] !== $username;
        });

        if (count($users) === count($newUsers)) {
            return false;
        }

        $this->saveUsers(array_values($newUsers));
        return true;
    }
}
