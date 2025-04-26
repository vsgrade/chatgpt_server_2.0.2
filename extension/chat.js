let chatHistory = {};
let currentChatId = null;
let server = localStorage.getItem("server");
let userId = localStorage.getItem("user_id");

// Показываем версию из manifest.json
if (typeof chrome !== "undefined" && chrome.runtime && chrome.runtime.getManifest) {
  const ver = chrome.runtime.getManifest().version;
  document.addEventListener("DOMContentLoaded", () => {
    const verEl = document.getElementById("appVer");
    if (verEl) verEl.innerText = ver;
  });
}

// Загрузка списка моделей с сервера
function loadModels() {
  fetch(server + "models.php")
    .then(res => res.json())
    .then(models => {
      const select = document.getElementById("modelSelect");
      select.innerHTML = "";
      if (!Array.isArray(models) || models.length === 0) {
        select.innerHTML = `<option value="">Нет доступных моделей</option>`;
        return;
      }
      models.forEach(id => {
        let label = id.replace(/-/g, " ").toUpperCase();
        select.innerHTML += `<option value="${id}">${label}</option>`;
      });
    })
    .catch(() => {
      const select = document.getElementById("modelSelect");
      select.innerHTML = `
        <option value="gpt-3.5-turbo">GPT-3.5</option>
        <option value="gpt-4">GPT-4</option>
      `;
    });
}

function renderChatList() {
  const list = document.getElementById("chatList");
  list.innerHTML = '';
  Object.keys(chatHistory).forEach(chatId => {
    const chat = chatHistory[chatId];
    const div = document.createElement("div");
    div.className = "chat-item" + (parseInt(chatId) === currentChatId ? " active" : "");
    // Название
    const name = document.createElement("span");
    name.className = "chat-title";
    name.innerText = chat.name || ("Чат " + chatId);
    div.appendChild(name);

    // Кнопки действий
    const actions = document.createElement("span");
    actions.className = "chat-actions";

    // Редактировать
    const editBtn = document.createElement("button");
    editBtn.title = "Переименовать";
    editBtn.innerHTML = "✏️";
    editBtn.onclick = e => {
      e.stopPropagation();
      let newName = prompt("Введите новое название чата", chat.name || "");
      if (newName) {
        chat.name = newName;
        renderChatList();
      }
    };
    actions.appendChild(editBtn);

    // Удалить
    const delBtn = document.createElement("button");
    delBtn.title = "Удалить чат";
    delBtn.innerHTML = "🗑️";
    delBtn.onclick = e => {
      e.stopPropagation();
      if (confirm("Удалить этот чат?")) {
        delete chatHistory[chatId];
        if (parseInt(chatId) === currentChatId) {
          // Открыть первый чат, если есть
          const ids = Object.keys(chatHistory);
          currentChatId = ids.length ? parseInt(ids[0]) : null;
        }
        renderChatList();
        renderMessages();
      }
    };
    actions.appendChild(delBtn);

    div.appendChild(actions);

    // При клике открыть чат
    div.onclick = () => {
      currentChatId = parseInt(chatId);
      renderChatList();
      renderMessages();
    };

    list.appendChild(div);
  });
}

function newChat() {
  const id = Date.now();
  chatHistory[id] = { name: "Новый чат", messages: [] };
  currentChatId = id;
  renderChatList();
  renderMessages();
}

function renderMessages() {
  const messages = document.getElementById("messages");
  messages.innerHTML = "";
  if (!currentChatId || !chatHistory[currentChatId]) return;
  (chatHistory[currentChatId].messages || []).forEach(msg => {
    const div = document.createElement("div");
    div.className = "message " + msg.role;
    div.innerText = msg.role === "user" ? "Вы: " + msg.content : "GPT: " + msg.content;
    messages.appendChild(div);
  });
  messages.scrollTop = messages.scrollHeight;
}

async function sendMessage() {
  const input = document.getElementById("userInput");
  if (!input) {
    alert("Ошибка: Невозможно найти поле ввода");
    return;
  }
  const content = input.value.trim();
  if (!content) return;

  input.value = "";
  if (!chatHistory[currentChatId]) chatHistory[currentChatId] = { name: "Новый чат", messages: [] };
  chatHistory[currentChatId].messages.push({ role: "user", content });
  renderMessages();

  try {
    const res = await fetch(server + "chat.php", {
      method: "POST",
      headers: { 
        "Content-Type": "application/json",
        "Accept": "application/json"
      },
      body: JSON.stringify({
        user_id: userId,
        message: content,
        model: document.getElementById("modelSelect").value
      })
    });

    if (!res.ok) {
      const errorText = await res.text();
      let errorMessage = "Ошибка сервера";
      try {
        const errorJson = JSON.parse(errorText);
        errorMessage = errorJson.error || errorMessage;
      } catch(e) {}
      alert(errorMessage);
      return;
    }

    const contentType = res.headers.get("content-type");
    if (contentType && contentType.includes("application/json")) {
      const data = await res.json();
      if (data.success) {
        chatHistory[currentChatId].messages.push({ role: "bot", content: data.reply });
        renderMessages();
      } else {
        alert("Ошибка: " + data.error);
      }
    } else {
      alert("Ошибка: Сервер вернул неожиданный ответ");
    }
  } catch (error) {
    alert("Ошибка соединения с сервером: " + error.message);
  }
}

window.onload = () => {
  server = localStorage.getItem("server");
  userId = localStorage.getItem("user_id");

  if (!userId || !server) {
    alert("Ошибка: Вы не авторизованы");
    window.close();
    return;
  }

  loadModels();

  fetch(server + "auth.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      email: localStorage.getItem("email"),
      password: localStorage.getItem("password"),
      type: "login"
    })
  })
  .then(res => res.json())
  .then(res => {
    if (!res.success) {
      alert("Ошибка: Вы не авторизованы");
      window.close();
    } else {
      newChat();
    }
  })
  .catch(error => {
    alert("Ошибка: Вы не авторизованы");
    window.close();
  });
};

document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("newChatButton").onclick = newChat;
  const sendButton = document.getElementById("sendButton");
  if (sendButton) sendButton.onclick = sendMessage;
});
