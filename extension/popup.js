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
    // Логирование ошибки на сервере (если доступно и нужно)
    // fetch(server + "log.php", {
    //   method: "POST",
    //   headers: { "Content-Type": "application/json" },
    //   body: JSON.stringify({
    //     message: "Ошибка авторизации: " + error.message,
    //     file: "popup.js",
    //     line: "auth"
    //   })
    // });
  });
}

document.getElementById("loginBtn").addEventListener("click", () => auth("login"));
document.getElementById("registerBtn").addEventListener("click", () => auth("register"));
document.getElementById("logoutBtn").addEventListener("click", logout);

document.getElementById("startChatBtn").addEventListener("click", () => {
  // Открываем новое окно или вкладку с index.html расширения
  chrome.tabs.create({ url: chrome.runtime.getURL("index.html") });
});

window.addEventListener("DOMContentLoaded", () => {
  const email = localStorage.getItem("email");
  const password = localStorage.getItem("password");
  const savedServer = localStorage.getItem("server");
  const uid = localStorage.getItem("user_id");

  const serverInput = document.getElementById("server"); // Получаем элемент поля ввода

  if (email) document.getElementById("email").value = email;

  // --- ИЗМЕНЕННЫЙ БЛОК ДЛЯ УСТАНОВКИ АДРЕСА СЕРВЕРА ---
  if (savedServer) {
    // Если адрес сервера уже есть в localStorage, используем его
    serverInput.value = savedServer;
  } else {
    // Если нет сохраненного адреса, устанавливаем стандартный
    serverInput.value = "https://appvault.pro/php/br/chatgpt/server/api/";
  }
  // --- КОНЕЦ ИЗМЕНЕННОГО БЛОКА ---


  // Попытка автоматического входа, если email и сохраненный сервер есть
  if (email && serverInput.value && isValidEmail(email)) {
    // Используем serverInput.value, так как оно теперь содержит либо сохраненный, либо стандартный адрес
    fetch(serverInput.value + "auth.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password, type: "login" })
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        // Автоматический вход успешен, показываем информацию пользователя
        showUserInfo(email);
        // Сохраняем serverInput.value в localStorage, чтобы использовать точное значение (например, с http/https)
        localStorage.setItem("server", serverInput.value);
      } else {
        // Автоматический вход не удался, очищаем сохраненные данные авторизации
        localStorage.clear();
      }
    })
    .catch(() => {
        // Ошибка при автоматическом входе, очищаем сохраненные данные
        localStorage.clear();
    });
  }
});

// Сохраняем введенный адрес сервера в localStorage при каждом изменении поля
document.getElementById("email").addEventListener("input", (e) => {
  localStorage.setItem("email", e.target.value.trim());
});
document.getElementById("server").addEventListener("input", (e) => {
  let serverValue = e.target.value.trim();
  // Опционально можно добавить проверку и форматирование адреса перед сохранением
  // if (!serverValue.endsWith("/") && serverValue !== "") {
  //     serverValue += "/";
  // }
  // if (!serverValue.startsWith("http://") && !serverValue.startsWith("https://") && serverValue !== "") {
  //     serverValue = "https://" + serverValue; // Пример: добавляем https по умолчанию
  // }
  localStorage.setItem("server", serverValue);
});