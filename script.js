/**
 * script.js - Phiên bản 1.0.8
 * Logic: Chỉ xóa chữ và tạo placeholder KHI VÀ CHỈ KHI tìm thấy kết quả.
 */

function learnWords() {
    const keywordInput = document.getElementById('keyword');
    const keyword = keywordInput.value.trim();
    
    const wordsArea = document.getElementById('words-list');
    const sentencesArea = document.getElementById('sentences-list');
    const titleWords = document.getElementById('title-words');
    const titleSentences = document.getElementById('title-sentences');
    const msgArea = document.getElementById('message-area');

    if (!keyword) {
        msgArea.style.display = 'block';
        msgArea.innerHTML = '👋 Bé ơi, hãy nhập chữ vào ô trống nhé!';
        return;
    }

    // Hiển thị trạng thái đang tải
    msgArea.style.display = 'block';
    msgArea.innerHTML = '⏳ Đang tìm kiếm...';
    
    // Ẩn kết quả cũ trong lúc chờ
    wordsArea.innerHTML = '';
    sentencesArea.innerHTML = '';
    titleWords.style.display = 'none';
    titleSentences.style.display = 'none';

    // Gọi API
    fetch(`api.php?action=search&keyword=${encodeURIComponent(keyword)}`)
        .then(response => response.json())
        .then(data => {
            if (data.found) {
                // === TÌM THẤY: XỬ LÝ GIAO DIỆN Ở ĐÂY ===
                msgArea.style.display = 'none'; 

                // 1. Đổi chữ mờ (Placeholder) thành từ vừa học
                keywordInput.placeholder = `Bé vừa học: ${keyword}`;
                
                // 2. Xóa sạch ô nhập để bé chạm vào là viết mới được ngay
                keywordInput.value = ''; 
                
                // 3. Ẩn bàn phím ảo
                keywordInput.blur();

                // 4. Hiển thị Từ vựng
                if (data.data.words && data.data.words.length > 0) {
                    titleWords.style.display = 'block';
                    data.data.words.forEach(item => {
                        const card = document.createElement('div');
                        card.className = 'word-card';
                        card.innerText = item.text;
                        card.onclick = () => speak(item.text);
                        wordsArea.appendChild(card);
                    });
                }

                // 5. Hiển thị Câu
                if (data.data.sentences && data.data.sentences.length > 0) {
                    titleSentences.style.display = 'block';
                    data.data.sentences.forEach(sentence => {
                        const card = document.createElement('div');
                        card.className = 'sentence-card';
                        card.innerHTML = `📚 ${sentence}`;
                        card.onclick = () => speak(sentence);
                        sentencesArea.appendChild(card);
                    });
                }
            } else {
                // === KHÔNG TÌM THẤY ===
                msgArea.innerHTML = `😢 Không tìm thấy bài nào có chữ "<strong>${data.keyword}</strong>".`;
                
                // Giữ nguyên chữ trong ô để bé biết mình sai ở đâu và sửa lại
                // (Không xóa value ở đây)
                keywordInput.focus(); // Đưa con trỏ chuột vào để sửa
            }
        })
        .catch(err => {
            console.error('Lỗi:', err);
            msgArea.innerHTML = '❌ Lỗi kết nối. Bé nhờ ba mẹ kiểm tra mạng nhé!';
        });
}

function speak(text) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
    }
    const url = `tts.php?text=${encodeURIComponent(text)}`;
    const audio = new Audio(url);
    audio.onerror = function() {
        if ('speechSynthesis' in window) {
             const utterance = new SpeechSynthesisUtterance(text);
             utterance.lang = 'vi-VN';
             window.speechSynthesis.speak(utterance);
        }
    };
    audio.play();
}

// Bắt sự kiện Enter
document.getElementById('keyword').addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
        learnWords();
    }
});