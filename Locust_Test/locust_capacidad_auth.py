from locust import HttpUser, task, constant
import random

class AuthUserCapacityTest(HttpUser):
    wait_time = constant(0)
    token = None

    def on_start(self):
        """Cada usuario obtiene su email único"""
        self.email = f"user_{random.randint(1, 100000)}@example.com"
        self.password = "password123"
        self.register_user()

    def register_user(self):
        """Se asegura que el usuario exista antes de hacer login"""
        payload = {
            "name": "Usuario Test",
            "email": self.email,
            "password": self.password
        }
        self.client.post("/api/register", json=payload)

    @task
    def login(self):
        """Login masivo"""
        payload = {
            "email": self.email,
            "password": self.password
        }
        with self.client.post("/api/login", json=payload, catch_response=True) as response:
            if response.status_code == 200:
                try:
                    data = response.json()
                    self.token = data.get("access_token") or data.get("token")
                    if self.token:
                        response.success()
                    else:
                        response.failure("Token no recibido")
                except Exception:
                    response.failure("Error parseando JSON del login")
            else:
                response.failure(f"Error en login: {response.status_code}")

    @task
    def logout(self):
        """Logout si el token existe"""
        if self.token:
            headers = {"Authorization": f"Bearer {self.token}"}
            with self.client.post("/api/logout", headers=headers, catch_response=True) as response:
                if response.status_code == 200:
                    response.success()
                    self.token = None
                else:
                    response.failure(f"Error en logout: {response.status_code}")
