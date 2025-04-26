let userId = null;
let server = "";

// Показываем версию из manifest.json
if (typeof chrome !== "undefined" && chrome.runtime && chrome.runtime.getManifest) {
  const ver = chrome.runtime.getManifest().version;
  document.addEventListener("DOMContentLoaded", () => {
    const verEl = document.getElementById("appVer");
    if (verEl) verEl.innerText = ver;
  });
}

function showError(message) {
  const el = document.getElementById("authError");
  el.innerText = message;
  el.style.display = "block";
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showUserInfo(email) {
  const authBlock = document.getElementById("authBlock");
  const userInfo = document.getElementById("userInfo");
  if (authBlock) authBlock.style.display = "none";
  if (userInfo) userInfo.style.display = "block";
  document.getElementById("userEmail").innerText = "Вы вошли как: " + email;
}

function logout() {
  localStorage.clear();
  location.reload();
}

function auth(type) {
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();
  server = document.getElementById("server").value.trim();
  
  if (!email || !password || !server) return showError("Заполните все поля");
  if (!isValidEmail(email)) return showError("Некорректный email");
  if (!server.endsWith("/")) server += "/";

  fetch(server + "auth.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email, password, type })
  })
  .then(res => {
    if (!res.ok) throw new Error("Сервер вернул ошибку");
    return res.json();
  })
  .then(res => {
    if (res.success) {
      userId = res.user_id;
      localStorage.setItem("email", email);
      localStorage.setItem("password", password);
      localStorage.setItem("server", server);
      localStorage.setItem("user_id", userId);
      showUserInfo(email);
    } else {
      showError(res.error || "Ошибка авторизации");
    }
  })
  .catch(error => {
    showError("Ошибка соединения с сервером: " + error.message);
    fetch(server + "log.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        message: "Ошибка авторизации: " + error.message,
        file: "popup.js",
        line: "auth"
      })
    });
  });
}

document.getElementById("loginBtn").addEventListener("click", () => auth("login"));
document.getElementById("registerBtn").addEventListener("click", () => auth("register"));
document.getElementById("logoutBtn").addEventListener("click", logout);

document.getElementById("startChatBtn").addEventListener("click", () => {
  chrome.tabs.create({ url: chrome.runtime.getURL("index.html") });
});

window.addEventListener("DOMContentLoaded", () => {
  const email = localStorage.getItem("email");
  const password = localStorage.getItem("password");
  const savedServer = localStorage.getItem("server");
  const uid = localStorage.getItem("user_id");
  
  if (email) document.getElementById("email").value = email;
  if (savedServer) document.getElementById("server").value = savedServer;
  
  if (email && savedServer && isValidEmail(email)) {
    fetch(savedServer + "auth.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password, type: "login" })
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        showUserInfo(email);
      } else {
        localStorage.clear();
      }
    })
    .catch(() => localStorage.clear());
  }
});

document.getElementById("email").addEventListener("input", (e) => {
  localStorage.setItem("email", e.target.value.trim());
});
document.getElementById("server").addEventListener("input", (e) => {
  localStorage.setItem("server", e.target.value.trim());
});
