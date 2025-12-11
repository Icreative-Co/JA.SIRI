/* ====================== GLOBAL FLAGS ====================== */
let isUserScrolling = false;
let scrollBtnVisible = false;

/* ====================== DOM ELEMENTS ====================== */
const chatForm       = document.getElementById('chat-form');
const userInput      = document.getElementById('user-input');
const voiceBtn       = document.getElementById('voice-input');
const chatMessages   = document.getElementById('chat-messages');
const exportBtn      = document.getElementById('export-chat');
const langToggle     = document.getElementById('lang-toggle');
const mobileToggle   = document.getElementById('mobile-menu-toggle');
const mobileMenu     = document.getElementById('mobile-menu');
const closeMobile    = document.getElementById('close-mobile-menu');
const infoBtn        = document.getElementById('info-button');
const infoModal      = document.getElementById('info-modal');
const closeInfo      = document.getElementById('close-info-modal');
const suggestionBtns = document.querySelectorAll('.suggestion-button');
const topicBtns      = document.querySelectorAll('.topic-button');
const scrollBtn      = document.getElementById('scroll-bottom');

/* ====================== GEMINI CONFIG ====================== */
// Gemini endpoint is called server-side via `api/chat.php`. Do NOT place API keys in client-side code.

let currentLang = 'en'; // 'en' or 'sw'

/* ====================== TRANSLATIONS ====================== */
const translations = {
    en: {
        welcome: "Jambo! I'm the Kenya Scouts AI Assistant. Ask me anything about KSA programs, badges, camps, or the Scout Promise.",
        placeholder: "Ask anything about Kenya Scouts…"
    },
    sw: {
        welcome: "Jambo! Mimi ni Msaidizi wa AI wa Skauti Kenya. Uliza chochote kuhusu programu za KSA, beji, kambi, au Ahadi ya Skauti.",
        placeholder: "Uliza chochote kuhusu Skauti Kenya…"
    }
};

/* ====================== SCROLL HANDLING ====================== */
chatMessages.addEventListener('scroll', () => {
    isUserScrolling = true;
    const atBottom = chatMessages.scrollHeight - chatMessages.scrollTop <= chatMessages.clientHeight + 120;
    if (!atBottom && !scrollBtnVisible) {
        scrollBtn.classList.remove('hidden');
        scrollBtn.classList.add('visible');
        scrollBtnVisible = true;
    } else if (atBottom && scrollBtnVisible) {
        scrollBtn.classList.add('hidden');
        scrollBtnVisible = false;
    }
    setTimeout(() => isUserScrolling = false, 150);
});
scrollBtn.addEventListener('click', () => {
    chatMessages.scrollTop = chatMessages.scrollHeight;
});

/* ====================== LANGUAGE SWITCH ====================== */
function switchLanguage(lang) {
    currentLang = lang;
    document.querySelectorAll('[data-en]').forEach(el => {
        el.textContent = el.getAttribute(`data-${lang}`);
    });
    userInput.placeholder = translations[lang].placeholder;
    langToggle.querySelector('.flag-icon').textContent = lang.toUpperCase();

    suggestionBtns.forEach(btn => {
        btn.textContent = btn.getAttribute(`data-${lang}`);
    });
}
langToggle.addEventListener('click', () => {
    switchLanguage(currentLang === 'en' ? 'sw' : 'en');
});

/* ====================== VOICE INPUT ====================== */
let recognition;
if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.lang = 'en-US';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    voiceBtn.addEventListener('click', () => {
        recognition.lang = currentLang === 'sw' ? 'sw-KE' : 'en-KE';
        recognition.start();
        voiceBtn.innerHTML = '<i class="fas fa-stop"></i>';
    });

    recognition.onresult = e => {
        const transcript = e.results[0][0].transcript;
        userInput.value = transcript;
        voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
    };
    recognition.onend = () => voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
    recognition.onerror = () => {
        voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
        alert("Voice input failed. Please try again.");
    };
} else {
    voiceBtn.style.display = 'none';
}

/* ====================== EXPORT CHAT ====================== */
exportBtn.addEventListener('click', () => {
    let chatText = "Kenya Scouts AI Chat\n";
    chatText += "=".repeat(40) + "\n\n";
    document.querySelectorAll('#chat-messages .chat-message').forEach(msg => {
        const isUser = msg.classList.contains('user-message');
        const text = msg.querySelector('p')?.textContent || '';
        chatText += `${isUser ? 'You' : 'Assistant'}: ${text}\n\n`;
    });
    const blob = new Blob([chatText], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `KSA_Chat_${new Date().toISOString().slice(0,10)}.txt`;
    a.click();
    URL.revokeObjectURL(url);
});

/* ====================== GEMINI CALL ====================== */
async function getGeminiResponse(prompt) {
    try {
        const res = await fetch('api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt })
        });

        if (!res.ok) {
            const err = await res.text();
            throw new Error(`Proxy ${res.status}: ${err}`);
        }

        const data = await res.json();
        return data.candidates?.[0]?.content?.parts?.[0]?.text ?? 'No answer.';
    } catch (e) {
        console.error(e);
        return currentLang === 'en'
            ? "Sorry, I can't reach the knowledge base right now."
            : "Samahani, siwezi kufikia msingi wa maarifa sasa hivi.";
    }
}

/* ====================== MESSAGE UI ====================== */
function addMessage(text, isUser = false) {
    const div = document.createElement('div');
    div.className = `chat-message p-3 mb-4 shadow-sm ${isUser ? 'user-message ml-auto' : 'bot-message mr-auto'}`;
    div.style.maxWidth = '75%';

    if (isUser) {
        const p = document.createElement('p');
        p.className = 'text-gray-800';
        p.textContent = text;
        div.appendChild(p);
    } else {
        const wrap = document.createElement('div');
        wrap.className = 'flex items-start gap-2';
        const avatar = document.createElement('div');
        avatar.className = 'w-8 h-8 rounded-full bg-green-700 flex items-center justify-center text-yellow-300';
        avatar.innerHTML = '<i class="fas fa-robot"></i>';
        const txt = document.createElement('p');
        txt.className = 'text-gray-800 flex-1';
        txt.textContent = text;
        wrap.append(avatar, txt);
        div.appendChild(wrap);
    }
    chatMessages.appendChild(div);
    if (!isUserScrolling) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
        if (scrollBtnVisible) {
            scrollBtn.classList.add('hidden');
            scrollBtnVisible = false;
        }
    }
}

/* ====================== TYPING INDICATOR ====================== */
function addTyping() {
    const div = document.createElement('div');
    div.id = 'typing-indicator';
    div.className = 'chat-message bot-message p-3 mb-4 shadow-sm mr-auto';
    div.style.maxWidth = '75%';
    const wrap = document.createElement('div');
    wrap.className = 'flex items-start gap-2';
    const avatar = document.createElement('div');
    avatar.className = 'w-8 h-8 rounded-full bg-green-700 flex items-center justify-center text-yellow-300';
    avatar.innerHTML = '<i class="fas fa-robot"></i>';
    const dots = document.createElement('div');
    dots.className = 'typing-indicator';
    dots.innerHTML = '<span></span><span></span><span></span>';
    wrap.append(avatar, dots);
    div.appendChild(wrap);
    chatMessages.appendChild(div);
    if (!isUserScrolling) chatMessages.scrollTop = chatMessages.scrollHeight;
}
function removeTyping() {
    document.getElementById('typing-indicator')?.remove();
}

/* ====================== FORM HANDLER ====================== */
let lastSubmissionTime = 0;
const SUBMISSION_COOLDOWN = 1000; // 1 second cooldown

async function handleSubmission() {
    const now = Date.now();
    if (now - lastSubmissionTime < SUBMISSION_COOLDOWN) {
        console.log('Please wait before sending another message');
        return;
    }
    
    const msg = userInput.value.trim();
    if (!msg) return;
    
    lastSubmissionTime = now;
    addMessage(msg, true);
    userInput.value = '';
    addTyping();
    
    try {
        const answer = await getGeminiResponse(msg);
        removeTyping();
        addMessage(answer);
    } catch (error) {
        removeTyping();
        addMessage('An error occurred. Please try again later.');
        console.error('Submission error:', error);
    }
}

chatForm.addEventListener('submit', e => { 
    e.preventDefault(); 
    handleSubmission(); 
});

/* ====================== SUGGESTIONS & TOPICS ====================== */
suggestionBtns.forEach(btn => btn.addEventListener('click', () => {
    userInput.value = btn.getAttribute(`data-${currentLang}`);
    handleSubmission();
}));
topicBtns.forEach(btn => btn.addEventListener('click', () => {
    userInput.value = `Tell me about ${btn.getAttribute(`data-${currentLang}`)}`;
    handleSubmission();
    mobileMenu.classList.replace('flex', 'hidden');
}));

/* ====================== MOBILE MENU ====================== */
mobileToggle?.addEventListener('click', () => mobileMenu.classList.replace('hidden', 'flex'));
closeMobile?.addEventListener('click', () => mobileMenu.classList.replace('flex', 'hidden'));

/* ====================== INFO MODAL ====================== */
infoBtn.addEventListener('click', () => infoModal.classList.replace('hidden', 'flex'));
closeInfo.addEventListener('click', () => infoModal.classList.replace('flex', 'hidden'));

/* Close modals on backdrop click */
window.addEventListener('click', e => {
    if (e.target === mobileMenu) mobileMenu.classList.replace('flex', 'hidden');
    if (e.target === infoModal) infoModal.classList.replace('flex', 'hidden');
});

/* ====================== INITIAL MESSAGE ====================== */
addMessage(translations[currentLang].welcome);

/* Focus input on load */
window.addEventListener('load', () => userInput.focus());