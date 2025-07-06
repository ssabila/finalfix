// Authentication functionality
class AuthManager {
  constructor() {
    this.baseUrl = "api"
    this.currentUser = null
    this.allowedDomains = ['stis.ac.id', 'bps.go.id']
    this.init()
  }

  init() {
    this.checkAuthStatus()
    this.setupAuthForms()
  }

  async checkAuthStatus() {
    try {
      const response = await fetch(`${this.baseUrl}/user/profile.php`, {
        method: "GET",
        credentials: "include",
      })

      if (response.ok) {
        const data = await response.json()
        this.currentUser = data.user
        this.updateNavigation(true)
      } else {
        this.currentUser = null
        this.updateNavigation(false)
      }
    } catch (error) {
      console.error("Auth check error:", error)
      this.currentUser = null
      this.updateNavigation(false)
    }
  }

  updateNavigation(isAuthenticated) {
    const navAuth = document.querySelector(".nav-auth")
    if (!navAuth) return

    if (isAuthenticated && this.currentUser) {
      navAuth.innerHTML = `
                <a href="profile.php" class="btn-login">
                    <i class="fas fa-user"></i>
                    ${this.currentUser.first_name}
                </a>
                <a href="#" class="btn-register" onclick="authManager.logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    Keluar
                </a>
            `
    } else {
      navAuth.innerHTML = `
                <a href="login.php" class="btn-login">Masuk</a>
                <a href="register.php" class="btn-register">Daftar</a>
            `
    }
  }

  setupAuthForms() {
    // Login form
    const loginForm = document.getElementById("login-form")
    if (loginForm) {
      loginForm.addEventListener("submit", (e) => this.handleLogin(e))
    }

    // Register form
    const registerForm = document.getElementById("register-form")
    if (registerForm) {
      registerForm.addEventListener("submit", (e) => this.handleRegister(e))
      this.setupEmailValidation(registerForm)
    }

    // Password toggle buttons
    const toggleButtons = document.querySelectorAll(".toggle-password")
    toggleButtons.forEach((button) => {
      button.addEventListener("click", this.togglePassword)
    })
  }

  setupEmailValidation(form) {
    const emailInput = form.querySelector('input[name="email"]')
    if (emailInput) {
      emailInput.addEventListener('blur', (e) => {
        this.validateEmailDomain(e.target)
      })
      
      emailInput.addEventListener('input', (e) => {
        // Clear custom validity when user starts typing
        e.target.setCustomValidity('')
      })
    }
  }

  validateEmailDomain(emailInput) {
    const email = emailInput.value.trim()
    if (!email) return true

    const emailDomain = email.split('@')[1]
    
    if (!emailDomain) {
      emailInput.setCustomValidity('Format email tidak valid')
      return false
    }

    if (!this.allowedDomains.includes(emailDomain)) {
      emailInput.setCustomValidity('Email harus berakhiran @stis.ac.id atau @bps.go.id')
      emailInput.reportValidity()
      return false
    }

    emailInput.setCustomValidity('')
    return true
  }

  async handleLogin(e) {
    e.preventDefault()

    const form = e.target
    const submitBtn = form.querySelector('button[type="submit"]')
    const formData = new FormData(form)

    // Show loading state
    submitBtn.classList.add("loading")
    submitBtn.disabled = true

    try {
      const response = await fetch(`${this.baseUrl}/auth/login.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify({
          email: formData.get("email"),
          password: formData.get("password"),
        }),
      })

      const data = await response.json()

      if (response.ok) {
        this.currentUser = data.user
        this.showNotification("Login berhasil! Mengalihkan ke profil...", "success")

        // Redirect to profile page after short delay
        setTimeout(() => {
          window.location.href = "profile.php"
        }, 1500)
      } else {
        this.showNotification(data.error || "Login gagal", "error")
      }
    } catch (error) {
      console.error("Login error:", error)
      this.showNotification("Terjadi kesalahan saat login", "error")
    } finally {
      submitBtn.classList.remove("loading")
      submitBtn.disabled = false
    }
  }

  async handleRegister(e) {
    e.preventDefault()

    const form = e.target
    const submitBtn = form.querySelector('button[type="submit"]')
    const formData = new FormData(form)

    // Validate email domain first
    const emailInput = form.querySelector('input[name="email"]')
    if (!this.validateEmailDomain(emailInput)) {
      this.showNotification("Email harus berakhiran @stis.ac.id atau @bps.go.id", "error")
      return
    }

    // Validate password confirmation
    const password = formData.get("password")
    const confirmPassword = formData.get("confirmPassword")

    if (password !== confirmPassword) {
      this.showNotification("Konfirmasi password tidak cocok", "error")
      return
    }

    // Show loading state
    submitBtn.classList.add("loading")
    submitBtn.disabled = true

    try {
      const response = await fetch(`${this.baseUrl}/auth/register.php`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          firstName: formData.get("firstName"),
          lastName: formData.get("lastName"),
          nim: formData.get("nim"),
          email: formData.get("email"),
          phone: formData.get("phone"),
          password: password,
          confirmPassword: confirmPassword,
        }),
      })

      const data = await response.json()

      if (response.ok) {
        this.showNotification("Registrasi berhasil! Silakan login.", "success")

        // Redirect to login page after short delay
        setTimeout(() => {
          window.location.href = "login.php"
        }, 2000)
      } else {
        this.showNotification(data.error || "Registrasi gagal", "error")
      }
    } catch (error) {
      console.error("Register error:", error)
      this.showNotification("Terjadi kesalahan saat registrasi", "error")
    } finally {
      submitBtn.classList.remove("loading")
      submitBtn.disabled = false
    }
  }

  async logout() {
    try {
      const response = await fetch(`${this.baseUrl}/auth/logout.php`, {
        method: "POST",
        credentials: "include",
      })

      if (response.ok) {
        this.currentUser = null
        this.showNotification("Logout berhasil", "success")

        // Redirect to home page
        setTimeout(() => {
          window.location.href = "index.php"
        }, 1000)
      } else {
        this.showNotification("Logout gagal", "error")
      }
    } catch (error) {
      console.error("Logout error:", error)
      this.showNotification("Terjadi kesalahan saat logout", "error")
    }
  }

  togglePassword(e) {
    const button = e.currentTarget
    const input = button.parentElement.querySelector("input")
    const icon = button.querySelector("i")

    if (input.type === "password") {
      input.type = "text"
      icon.classList.remove("fa-eye")
      icon.classList.add("fa-eye-slash")
    } else {
      input.type = "password"
      icon.classList.remove("fa-eye-slash")
      icon.classList.add("fa-eye")
    }
  }

  showNotification(message, type = "info") {
    // Remove existing notifications
    const existing = document.querySelector(".notification")
    if (existing) {
      existing.remove()
    }

    const notification = document.createElement("div")
    notification.className = `notification notification-${type}`
    notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${type === "success" ? "fa-check-circle" : type === "error" ? "fa-exclamation-circle" : "fa-info-circle"}"></i>
                <span>${message}</span>
            </div>
        `

    document.body.appendChild(notification)

    // Show notification
    setTimeout(() => {
      notification.classList.add("show")
    }, 100)

    // Hide notification after 5 seconds
    setTimeout(() => {
      notification.classList.remove("show")
      setTimeout(() => {
        notification.remove()
      }, 300)
    }, 5000)
  }
}

// Initialize auth manager when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  window.authManager = new AuthManager()
})