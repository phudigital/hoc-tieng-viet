// script.js
function learnWords() {
    const keywordInput = document.getElementById('keyword');
    const keyword = keywordInput.value.trim();
    
    const wordsArea = document.getElementById('words-list');
    const sentencesArea = document.getElementById('sentences-list');
    const titleWords = document.getElementById('title-words');
    const titleSentences = document.getElementById('title-sentences');
    const msgArea = document.getElementById('message-area');

    // Reset giao diện
    wordsArea.innerHTML = '';
    sentencesArea.innerHTML = '';
    titleWords.style.display = 'none';
    titleSentences.style.display = 'none';
    msgArea.style.display = 'block';
    msgArea.innerHTML = '⏳ Đang tìm kiếm...';

    if (!keyword) {
        msgArea.innerHTML = '👋 Bé ơi, nhập chữ vào ô nhé!';
        return;
    }

    fetch(`api.php?action=search&keyword=${encodeURIComponent(keyword)}`)
        .then(response => response.json())
        .then(data => {
            if (data.found) {
                msgArea.style.display = 'none';

                // 1. Hiển thị Từ vựng (Chỉ hiển thị chữ)
                if (data.data.words && data.data.words.length > 0) {
                    titleWords.style.display = 'block';
                    data.data.words.forEach(item => {
                        const card = document.createElement('div');
                        card.className = 'word-card';
                        card.innerText = item.text; // Chỉ lấy text
                        
                        // Click để đọc
                        card.onclick = () => speak(item.text);
                        wordsArea.appendChild(card);
                    });
                }

                // 2. Hiển thị Câu
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
                msgArea.innerHTML = `😢 Không tìm thấy bài nào có chữ "<strong>${data.keyword}</strong>".`;
            }
        })
        .catch(err => {
            console.error(err);
            msgArea.innerHTML = '❌ Có lỗi kết nối.';
        });
}

function speak(text) {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'vi-VN';
        utterance.rate = 0.8;
        window.speechSynthesis.speak(utterance);
    }
}

document.getElementById('keyword').addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
        learnWords();
    }
});