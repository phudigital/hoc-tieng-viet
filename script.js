/**
 * script.js - Version 4.0 (Hiển thị Local ngay lập tức, AI tải sau)
 */

let savedVipPassword = ""; 
// Biến lưu trữ danh sách từ đã hiển thị để tránh AI trùng lặp
let displayedWords = new Set(); 

// Toggle Switch Logic
document.getElementById('aiToggle').addEventListener('change', function() {
    const label = document.getElementById('aiLabel');
    if (this.checked) {
        label.innerText = "Chế độ AI: BẬT ✨";
        label.style.color = "#9C27B0";
    } else {
        label.innerText = "Chế độ AI: Tắt";
        label.style.color = "#666";
    }
});

function learnWords() {
    const keywordInput = document.getElementById('keyword');
    const keyword = keywordInput.value.trim();
    const aiToggle = document.getElementById('aiToggle').checked;
    
    // UI Elements
    const wordsArea = document.getElementById('words-list');
    const sentencesArea = document.getElementById('sentences-list');
    const titleWords = document.getElementById('title-words');
    const titleSentences = document.getElementById('title-sentences');
    const msgArea = document.getElementById('message-area');

    if (!keyword) {
        msgArea.style.display = 'block';
        msgArea.innerHTML = '👋 Bé ơi, nhập chữ vào ô nhé!';
        return;
    }

    // --- UX: DỌN DẸP ---
    keywordInput.placeholder = `Bé đang học: ${keyword}`;
    keywordInput.value = '';
    keywordInput.blur();
    
    wordsArea.innerHTML = '';
    sentencesArea.innerHTML = '';
    titleWords.style.display = 'none';
    titleSentences.style.display = 'none';
    displayedWords.clear(); // Xóa bộ nhớ từ cũ

    // --- BƯỚC 1: TÌM TRONG SÁCH (CHẠY NGAY) ---
    msgArea.style.display = 'block';
    msgArea.innerHTML = '⏳ Đang tìm trong sách...';

    fetch(`api.php?action=search&keyword=${encodeURIComponent(keyword)}`)
        .then(res => res.json())
        .then(data => {
            // Xử lý dữ liệu sách
            if (data.found) {
                renderContent(data, false); // false = không phải AI
                // Nếu tìm thấy sách, xóa thông báo loading ngay (nếu không bật AI)
                if (!aiToggle) msgArea.style.display = 'none';
            } else {
                if (!aiToggle) msgArea.innerHTML = `😢 Trong sách không có bài này. Bé hãy bật AI thử xem!`;
            }

            // --- BƯỚC 2: GỌI AI (NẾU BẬT) ---
            if (aiToggle) {
                msgArea.style.display = 'block';
                msgArea.innerHTML = data.found ? 
                    '📚 Đã tìm thấy trong sách. Đang hỏi thêm thầy giáo AI...' : 
                    '⏳ Đang hỏi thầy giáo AI...';

                callAI(keyword, msgArea);
            }
        })
        .catch(err => {
            console.error(err);
            msgArea.innerHTML = '❌ Lỗi kết nối.';
        });
}

function callAI(keyword, msgArea) {
    // Gọi API AI độc lập
    fetch(`api.php?action=ask_ai&keyword=${encodeURIComponent(keyword)}&password=${encodeURIComponent(savedVipPassword)}`)
        .then(res => res.json())
        .then(aiData => {
            // Ẩn thông báo loading khi AI xong
            msgArea.style.display = 'none';

            if (aiData.error_code === 'LIMIT_REACHED') {
                handleLimitReached(msgArea);
                return;
            }

            if (aiData.found) {
                renderContent(aiData, true); // true = là AI
            } else {
                // Nếu AI cũng không tìm thấy và trước đó sách cũng không thấy
                if (document.getElementById('words-list').children.length === 0) {
                    msgArea.style.display = 'block';
                    msgArea.innerHTML = `😢 Không tìm thấy kết quả nào cho "${keyword}".`;
                }
            }
        })
        .catch(err => {
            console.error(err);
            msgArea.style.display = 'block';
            msgArea.innerHTML = '❌ Lỗi kết nối AI.';
        });
}

// Hàm render chung cho cả 2 luồng
function renderContent(data, isAI) {
    const titleWords = document.getElementById('title-words');
    const titleSentences = document.getElementById('title-sentences');
    const wordsArea = document.getElementById('words-list');
    const sentencesArea = document.getElementById('sentences-list');

    // 1. TỪ VỰNG
    if (data.data.words && data.data.words.length > 0) {
        titleWords.style.display = 'block';
        data.data.words.forEach(item => {
            // Kiểm tra trùng lặp (Case insensitive)
            const lowerText = item.text.toLowerCase();
            if (!displayedWords.has(lowerText)) {
                
                displayedWords.add(lowerText); // Đánh dấu đã hiện

                const card = document.createElement('div');
                // Nếu là từ AI thì thêm class ai-style
                card.className = (isAI || item.is_ai) ? 'word-card ai-style' : 'word-card';
                card.innerText = item.text;
                
                // Hiệu ứng xuất hiện
                card.style.animation = 'popIn 0.5s ease-out';
                
                card.onclick = () => speak(item.text);
                wordsArea.appendChild(card);
            }
        });
    }

    // 2. CÂU
    if (data.data.sentences && data.data.sentences.length > 0) {
        titleSentences.style.display = 'block';
        data.data.sentences.forEach(sentence => {
            const card = document.createElement('div');
            card.className = 'sentence-card';
            
            const icon = isAI ? '🤖' : '📚';
            card.innerHTML = `${icon} ${sentence}`;
            
            if (isAI) {
                card.style.borderLeftColor = '#9C27B0';
                card.style.backgroundColor = '#F3E5F5';
            }

            card.style.animation = 'popIn 0.5s ease-out';
            card.onclick = () => speak(sentence);
            sentencesArea.appendChild(card);
        });
    }
}

function handleLimitReached(msgArea) {
    document.getElementById('aiToggle').checked = false;
    document.getElementById('aiLabel').innerText = "Chế độ AI: Tắt (Hết lượt)";
    document.getElementById('aiLabel').style.color = "#666";
    document.getElementById('passwordModal').style.display = 'block';
    document.getElementById('vipPassword').focus();
    msgArea.style.display = 'block';
    msgArea.innerHTML = '⚠️ Hết lượt AI miễn phí.';
}

function cancelVip() {
    document.getElementById('passwordModal').style.display = 'none';
}

function confirmVip() {
    const inputPass = document.getElementById('vipPassword').value;
    if (inputPass) {
        savedVipPassword = inputPass;
        document.getElementById('passwordModal').style.display = 'none';
        document.getElementById('aiToggle').checked = true;
        document.getElementById('aiLabel').innerText = "Chế độ AI: BẬT ✨";
        document.getElementById('aiLabel').style.color = "#9C27B0";
        alert("Đã lưu mật khẩu. Mời bé tìm kiếm lại!");
    } else {
        alert("Vui lòng nhập mật khẩu.");
    }
}

function speak(text) {
    if ('speechSynthesis' in window) window.speechSynthesis.cancel();
    const url = `tts.php?text=${encodeURIComponent(text)}`;
    const audio = new Audio(url);
    audio.play();
}

// Thêm animation vào JS để đảm bảo chạy mượt
const styleSheet = document.createElement("style");
styleSheet.innerText = `
@keyframes popIn {
  from { opacity: 0; transform: scale(0.8); }
  to { opacity: 1; transform: scale(1); }
}`;
document.head.appendChild(styleSheet);

document.getElementById('keyword').addEventListener("keypress", function(event) {
    if (event.key === "Enter") learnWords();
});



// --- TÍNH NĂNG THỐNG KÊ TRUY CẬP (Mới) ---
function loadStats() {
    fetch('stats.php')
        .then(res => res.json())
        .then(data => {
            if (data) {
                document.getElementById('stat-day').innerText = data.day;
                document.getElementById('stat-month').innerText = data.month;
                document.getElementById('stat-total').innerText = data.total;
                // Hiện box lên sau khi tải xong
                document.getElementById('visitor-stats').style.display = 'inline-flex';
            }
        })
        .catch(err => console.error('Lỗi tải thống kê:', err));
}

// Gọi hàm đếm ngay khi trang web tải xong
loadStats();