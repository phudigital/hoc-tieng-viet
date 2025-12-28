# Bé Học Tiếng Việt

## Mô Tả

Ứng dụng web đơn giản giúp bé học tiếng Việt lớp 1, hỗ trợ sách "Chân Trời Sáng Tạo". Bé có thể tìm từ vựng theo vần/chữ cái, nghe đọc từ và tập đọc câu/đoạn văn.

## Tính Năng Chính

- **Tìm Từ Vựng**: Nhập vần (a, b, ang...) để xem danh sách từ vựng liên quan.
- **Nghe Đọc**: Bấm vào từ để nghe giọng đọc tiếng Việt.
- **Tập Đọc Câu**: Mở rộng với câu và đoạn văn mẫu để bé luyện tập.
- **Giao Diện Thân Thiện**: Thiết kế vui nhộn, phù hợp trẻ em, responsive trên mobile.

## Cách Sử Dụng

1. Nhập vần hoặc chữ cái vào ô tìm kiếm (ví dụ: "a", "b", "ang").
2. Nhấn "Học từ" để xem danh sách từ.
3. Bấm vào từ để nghe đọc.
4. Luyện tập đọc câu/đoạn văn nếu có.

## Truy Cập Trực Tiếp

<a href="https://app.pdl.vn/hoc-tieng-viet/" target="_blank">[https://app.pdl.vn/hoc-tieng-viet/]</a>

## Cài Đặt & Chạy

### Yêu Cầu
- Máy chủ web hỗ trợ PHP.
- Trình duyệt có hỗ trợ Speech Synthesis API (hầu hết trình duyệt hiện đại).

### Bước Cài Đặt
1. Clone dự án:
   ```bash
   git clone <repository-url>
   cd hoc-tieng-viet
   ```

2. Chạy server:
   ```bash
   php -S localhost:8000
   ```

3. Truy cập: `http://localhost:8000/index.php`

### Cấu Trúc File
- `index.php`: Giao diện chính.
- `data.php`: Dữ liệu từ vựng, câu, đoạn văn.
- `functions.php`: Logic tìm kiếm.
- `styles.css`: CSS styling.
- `script.js`: JavaScript frontend.

## Bổ Sung Data

Data được lưu trong `data.php`. Để thêm từ vựng từ sách (mục lục trang 5-6 PDF):
- Thêm vào mảng `$dictionary` với key là vần/chủ đề.
- Bao gồm 'words', 'sentences', 'paragraphs'.

Ví dụ:
```php
'gia_dinh' => [
    'words' => ['ba', 'má', 'ông'],
    'sentences' => ['Gia đình em có ba má.'],
    'paragraphs' => ['Gia đình hạnh phúc...']
]
```

## Công Nghệ Sử Dụng

- **Backend**: PHP (không database).
- **Frontend**: HTML5, CSS3, JavaScript (AJAX, Speech API).
- **Responsive**: CSS Media Queries.

## Tác Giả

- **Phu Digital Vibe Coding**.
- Phiên bản: 1.0.

## Giấy Phép

MIT License.

## Đóng Góp

Chào đón đóng góp! Tạo issue hoặc PR trên [GitHub Repository](https://github.com/phudigital/hoc-tieng-viet).