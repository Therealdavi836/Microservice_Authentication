from locust import HttpUser, task, between
import random

class AuthUser(HttpUser):
    wait_time = between(1, 3)
    token = None

    @task(1)
    def register(self):
        """Prueba de registro de usuario"""
        unique_email = f"user_{random.randint(1,1000000)}@example.com"
        payload = {
            "name": "Test User",
            "email": unique_email,
            "password": "password123",
            "password_confirmation": "password123"
        }
        self.client.post("/api/register", json=payload)

    @task(2)
    def login(self):
        """Prueba de login"""
        payload = {
            "email": "user_test@example.com",  # este usuario debe existir en la DB
            "password": "password123"
        }
        response = self.client.post("/api/login", json=payload)
        if response.status_code == 200:
            data = response.json()
            self.token = data.get("token") or data.get("access_token")

    @task(3)
    def logout(self):
        """Prueba de logout (requiere token)"""
        if self.token:
            headers = {"Authorization": f"Bearer {self.token}"}
            self.client.post("/api/logout", headers=headers)
            self.token = None
