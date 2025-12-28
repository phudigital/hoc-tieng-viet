/**
 * script.js - Phiên bản 1.0.5
 * Chức năng: Xử lý tìm kiếm và Đọc giọng nói qua Proxy (tts.php)
 */

function learnWords() {
    // 1. Lấy các phần tử từ DOM
    const keywordInput = document.getElementById('keyword');
    const keyword = keywordInput.value.trim();
    
    const wordsArea = document.getElementById('words-list');
    const sentencesArea = document.getElementById('sentences-list');
    const titleWords = document.getElementById('title-words');
    const titleSentences = document.getElementById('title-sentences');
    const msgArea = document.getElementById('message-area');

    // 2. Reset giao diện về trạng thái ban đầu
    wordsArea.innerHTML = '';
    sentencesArea.innerHTML = '';
    titleWords.style.display = 'none';
    titleSentences.style.display = 'none';
    msgArea.style.display = 'block';
    
    // 3. Kiểm tra đầu vào
    if (!keyword) {
        msgArea.innerHTML = '👋 Bé ơi, hãy nhập chữ cái hoặc vần vào ô trống nhé!';
        return;
    }

    // Hiển thị trạng thái đang tải
    msgArea.innerHTML = '⏳ Đang tìm kiếm bài học cho bé...';

    // 4. Gọi API Backend (api.php)
    fetch(`api.php?action=search&keyword=${encodeURIComponent(keyword)}`)
        .then(response => response.json())
        .then(data => {
            // Xử lý kết quả trả về
            if (data.found) {
                msgArea.style.display = 'none'; // Ẩn thông báo

                // --- HIỂN THỊ TỪ VỰNG ---
                if (data.data.words && data.data.words.length > 0) {
                    titleWords.style.display = 'block';
                    
                    data.data.words.forEach(item => {
                        const card = document.createElement('div');
                        card.className = 'word-card';
                        
                        // Chỉ hiển thị chữ (theo yêu cầu bỏ topic)
                        card.innerText = item.text;
                        
                        // Sự kiện Click để đọc
                        card.onclick = () => speak(item.text);
                        
                        wordsArea.appendChild(card);
                    });
                }

                // --- HIỂN THỊ CÂU TẬP ĐỌC ---
                if (data.data.sentences && data.data.sentences.length > 0) {
                    titleSentences.style.display = 'block';
                    
                    data.data.sentences.forEach(sentence => {
                        const card = document.createElement('div');
                        card.className = 'sentence-card';
                        
                        // Thêm icon sách cho sinh động
                        card.innerHTML = `📚 ${sentence}`;
                        
                        // Sự kiện Click để đọc
                        card.onclick = () => speak(sentence);
                        
                        sentencesArea.appendChild(card);
                    });
                }
            } else {
                // Không tìm thấy kết quả
                msgArea.innerHTML = `😢 Không tìm thấy bài nào có chữ "<strong>${data.keyword}</strong>". Bé thử nhập vần khác nhé (ví dụ: a, b, ang)!`;
            }
        })
        .catch(err => {
            console.error('Lỗi kết nối:', err);
            msgArea.innerHTML = '❌ Có lỗi kết nối xảy ra. Nhờ ba mẹ kiểm tra lại mạng nhé!';
        });
}

/**
 * Hàm đọc văn bản (Text-to-Speech)
 * Sử dụng proxy tts.php để lấy giọng chuẩn Tiếng Việt từ Google
 * Fix lỗi Safari đọc tiếng Anh.
 */
function speak(text) {
    // Nếu trình duyệt đang đọc dở câu cũ, hãy dừng lại
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
    }

    // Gọi đến file trung gian tts.php trên server
    const url = `tts.php?text=${encodeURIComponent(text)}`;
    
    const audio = new Audio(url);
    
    // Xử lý khi bắt đầu phát (có thể thêm hiệu ứng loading nếu muốn)
    audio.onplay = function() {
        // console.log("Đang đọc: " + text);
    };

    // Xử lý lỗi (Fallback): Nếu không gọi được Google, dùng tạm giọng máy
    audio.onerror = function() {
        console.warn("Không tải được giọng Google, chuyển sang giọng mặc định của máy.");
        if ('speechSynthesis' in window) {
             const utterance = new SpeechSynthesisUtterance(text);
             utterance.lang = 'vi-VN'; // Cố gắng set tiếng Việt
             utterance.rate = 0.8;     // Đọc chậm
             window.speechSynthesis.speak(utterance);
        } else {
            alert("Máy không hỗ trợ phát âm thanh.");
        }
    };

    // Phát âm thanh
    audio.play();
}

// Bắt sự kiện nhấn phím Enter trong ô nhập liệu
document.getElementById('keyword').addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
        learnWords();
    }
});