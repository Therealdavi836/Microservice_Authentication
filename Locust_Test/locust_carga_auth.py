from locust import HttpUser, task, between
import random

class AuthUserLoadTest(HttpUser):
    wait_time = between(1, 3)
    token = None

    @task(1)
    def register(self):
        """Simula el registro de nuevos usuarios"""
        unique_email = f"user_{random.randint(1, 1000000)}@example.com"
        payload = {
            "name": "Usuario Test",
            "email": unique_email,
            "password": "password123"
        }
        with self.client.post("/api/register", json=payload, catch_response=True) as response:
            if response.status_code == 200:
                response.success()
            else:
                response.failure(f"Error en registro: {response.status_code}")

    @task(2)
    def login(self):
        """Simula el inicio de sesión"""
        payload = {
            "email": "user_test@example.com",  # usuario existente
            "password": "password123"
        }
        with self.client.post("/api/login", json=payload, catch_response=True) as response:
            if response.status_code == 200:
                data = response.json()
                self.token = data.get("access_token")
                response.success()
            else:
                response.failure(f"Error en login: {response.status_code}")

    @task(1)
    def logout(self):
        """Simula cierre de sesión solo si hay token"""
        if self.token:
            headers = {"Authorization": f"Bearer {self.token}"}
            with self.client.post("/api/logout", headers=headers, catch_response=True) as response:
                if response.status_code == 200:
                    response.success()
                    self.token = None
                else:
                    response.failure(f"Error en logout: {response.status_code}")
