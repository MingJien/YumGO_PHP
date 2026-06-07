# 🎨 YumGO UI/UX Design System & Agent Skill

Tài liệu này tổng hợp toàn bộ triết lý thiết kế, đặc tả giao diện (Design Specification), hệ thống token (Design Tokens), sơ đồ luồng người dùng (User Flow), và các quy tắc hiển thị dữ liệu để tạo thành một **Agent Skill chuyên biệt về thiết kế UI/UX** cho dự án YumGO.

---

## 🎯 1. Triết lý Thiết kế Lai (Hybrid Design Philosophy)

YumGO sở hữu một giao diện độc đáo được lai tạo từ hai hệ thống thiết kế hàng đầu thế giới:

```
                ┌──────────────────────────────────────────┐
                │             YumGO DESIGN SPEC            │
                └────────────────────┬─────────────────────┘
                                     │
                  ┌──────────────────┴──────────────────┐
                  ▼                                     ▼
         [ Vercel Inspiration ]                [ Airbnb Inspiration ]
      • Spacing hệ cơ sở 4px                • Bố cục Photo-first (ảnh là trung tâm)
      • Đổ bóng đa tầng (Stacked Shadows)   • Góc bo mềm mại (rounded-md: 12px)
      • Phân cấp Typography chặt chẽ        • Tông chủ đạo đơn sắc nổi bật (#FF6600)
      • Bố cục thoáng đãng, tinh tế         • Trải nghiệm khách hàng ấm áp, thân thiện
```

### 🧱 Triết lý áp dụng:
* **Ảnh món ăn là trung tâm (Photo-First):** Lấy cảm hứng từ Airbnb, hình ảnh món ăn phải chiếm ít nhất 60% diện tích thẻ (Food Card) và hiển thị sắc nét với thuộc tính `object-fit: cover`.
* **Giãn cách chặt chẽ & Đổ bóng tinh tế (Stacked Shadows):** Lấy cảm hứng từ Vercel, khoảng cách giữa các phần tử là bội số của 4px, sử dụng đổ bóng đa tầng (nhiều lớp bóng nhẹ xếp chồng) kết hợp viền mờ hairline (1px) thay vì bóng đổ đậm đen thô cứng.
* **Màu sắc kích thích vị giác:** Tông cam ấm (`#FF6600`) làm điểm nhấn thương hiệu (Brand Voltage) cho các CTA chính (nút thêm món, thanh toán), nổi bật trên nền xám siêu nhạt (`#F8F9FA`) của trang.

---

## 🎛️ 1.5. Hệ thống Ba Núm Xoay Thiết Kế & Nguyên Tắc Chống Rập Khuôn (The Three Dials & Anti-Slop Specs)

Để định lượng hóa và kiểm soát nhất quán ngôn ngữ thiết kế của YumGO trên mọi màn hình, chúng tôi thiết lập ba thông số cấu hình cốt lõi:
*   **`DESIGN_VARIANCE: 7`** (Độ biến thiên thiết kế): Bố cục trang chủ và danh mục sử dụng cấu trúc lưới bất đối xứng nhẹ, bento grids cách điệu, kết hợp đốm sáng và trôi nổi nhẹ để phá bỏ sự rập khuôn cân đối tẻ nhạt.
*   **`MOTION_INTENSITY: 6`** (Cường độ chuyển động): Các chuyển động vi mô (micro-motion) được cấu hình bằng Spring Physics (lực đàn hồi vật lý), bao gồm đĩa Hamburger trôi nổi nhẹ, hiệu ứng stagger load cho grid danh sách món và transition trượt mượt mà cho Drawer Cart.
*   **`VISUAL_DENSITY: 4`** (Mật độ trực quan): Bố cục thoáng đãng, nhiều không gian thở (`gap-4` hoặc `gap-5` chuẩn Vercel) để người dùng tập trung cao độ vào hình ảnh món ăn rực rỡ, không bị Data-dump gây rối mắt.

### 🚫 Các Quy Tắc Chống Rập Khuôn (Anti-Slop Discipline)
1.  **Kỷ luật Bố cục Hero (Hero Layout Discipline):**
    *   Vùng Hero phải nằm trọn trong viewport ban đầu. Headline tối đa 2 dòng. Phân cấp tối đa 4 phần tử chữ (Badge khuyến mãi -> Headline -> Subtext -> Ô tìm kiếm).
    *   Subtext mô tả tối đa **20 từ**, súc tích kích thích vị giác.
    *   Giới hạn đệm đỉnh (Top padding cap) ở desktop tối đa 96px (`pt-24`) để tránh đẩy nội dung chính xuống sâu gây read-error.
2.  **Ràng buộc Nhãn phụ (Eyebrow Restraint):** Tránh lạm dụng nhãn in hoa nhỏ (eyebrow) trên đầu các section. Tối đa 1 eyebrow cho mỗi 3 sections trên trang web (VD: Trang chủ chỉ dùng 1 eyebrow cho tiêu đề section món ăn nổi bật).
3.  **Nhất quán Góc Bo (Shape Consistency Lock):** Đồng bộ hóa hình học trên toàn bộ giao diện: các thẻ Card và ô nhập liệu được khóa cứng ở bo góc `rounded-md: 12px` (hoặc `.rounded-3`), các nút CTA và bộ chọn số lượng được khóa ở dạng `rounded-pill`. Không pha trộn các hình học đối lập bừa bãi.
4.  **Chống trùng lặp Bố cục (Section-Layout-Repetition Ban):** Mỗi kiểu bố cục (như Grid 3 cột, bento grid, hoặc split-layout) chỉ được xuất hiện tối đa **1 lần** trên toàn trang để giữ nhịp điệu cuộn luôn mới mẻ.
5.  **Bento Grid đa dạng (Bento Background Diversity):** Bento grid giới thiệu dịch vụ phải có sự tương phản nền rõ rệt (VD: thẻ Giao hàng có icon cam, thẻ Vệ sinh có icon xanh lá, nền thẻ xám mịn) thay vì 3 thẻ màu trắng trơn nhạt nhẽo.
6.  **WCAG Button & Form Contrast Check:** Toàn bộ chữ và icon trên nút bấm và form nhập liệu phải vượt qua tỷ lệ tương phản tối thiểu WCAG AA (4.5:1 đối với văn bản thường, 3:1 đối với văn bản lớn). Cấm các nút ghost hoặc button nhạt màu có độ tương phản kém.
7.  **Button Wrap Ban:** Chữ của nút bấm kêu gọi hành động (CTA) bắt buộc phải nằm trên một dòng duy nhất trên desktop, không được phép rớt dòng làm nát bố cục.

---

## 🎨 2. Hệ thống Tokens Thiết kế (Design Tokens)

### 2.1 Bảng màu (Color Palette)

```css
:root {
  /* Brand & Accent Colors */
  --yumgo-primary: #FF6600;           /* Cam nóng: Nút thêm món, CTA chính, giá tiền */
  --yumgo-primary-hover: #E85A00;     /* Trạng thái Hover của màu chính */
  --yumgo-primary-active: #CC4E00;    /* Trạng thái Active/Pressed */
  --yumgo-primary-soft: #FFF0E6;      /* Nền phụ cam nhạt cho vùng nhấn mạnh */
  --yumgo-secondary: #FFC107;         /* Vàng nghệ: Nhãn giảm giá SALE, phụ trợ */
  --yumgo-secondary-soft: #FFF8E1;    /* Nền nhãn SALE */

  /* Surface & Backgrounds */
  --yumgo-canvas: #FFFFFF;            /* Nền trắng tinh: Thẻ card, modal, bottom nav */
  --yumgo-canvas-soft: #F8F9FA;       /* Nền trang chính: Xám nhạt (.bg-light) sạch sẽ */
  --yumgo-canvas-warm: #FFF9F5;       /* Nền banner hoặc vùng chào mừng ấm áp */
  --yumgo-surface-card: #FFFFFF;      /* Nền của thẻ món ăn */

  /* Typography Colors */
  --yumgo-ink: #1A1A2E;               /* Đen đậm: Tiêu đề trang, tên món, nhãn chính */
  --yumgo-body: #4A4A68;              /* Xám đậm: Mô tả món ăn, nội dung văn bản chính */
  --yumgo-muted: #8E8EA0;             /* Xám nhạt: Placeholder, text bị mờ, icon inactive */
  --yumgo-on-primary: #FFFFFF;        /* Chữ trắng trên nền màu cam */

  /* Badges & Semantic Status */
  --yumgo-badge-hot-bg: #EF4444;      /* Nền nhãn HOT đỏ tươi */
  --yumgo-badge-sale-bg: #FFC107;     /* Nền nhãn SALE vàng */
  --yumgo-badge-unavailable-bg: #E5E7EB; /* Nền nhãn Tạm hết hàng xám nhạt */
  --yumgo-badge-unavailable-text: #6B7280; /* Chữ nhãn Tạm hết hàng xám đậm */
  --yumgo-success: #10B981;           /* Xanh lá: Thông báo thành công */
  --yumgo-error: #EF4444;             /* Đỏ: Thông báo lỗi nhập liệu */

  /* Borders & Dividers */
  --yumgo-hairline: #E5E7EB;          /* Đường kẻ viền mờ nhạt */
  --yumgo-hairline-soft: #F3F4F6;     /* Đường kẻ viền siêu mờ */
  --yumgo-border-focus: #FF6600;      /* Viền khi trỏ chuột vào ô nhập liệu */
}
```

### 2.2 Kiểu chữ (Typography)
* **Font Stack chính:** `'Inter', 'Roboto', -apple-system, sans-serif` (Ưu tiên font không chân).
* **Quy chuẩn tỷ lệ chữ:**
  * `display-hero` (Headline banner): `28px` / Bold 700 / Line height 1.2
  * `display-lg` (Tiêu đề trang lớn): `24px` / Bold 700 / Line height 1.3
  * `display-md` (Tiêu đề phân đoạn): `20px` / Semibold 600 / Line height 1.35
  * `title-lg` (Tên món ăn chi tiết): `16px` / Semibold 600 / Line height 1.4
  * `title-md` (Tên món trên card/giỏ): `15px` / Semibold 600 / Line height 1.4
  * `body-lg` (Mô tả chi tiết món ăn): `16px` / Regular 400 / Line height 1.5
  * `body-md` (Mô tả trên card): `14px` / Regular 400 / Line height 1.5
  * `price-display` (Giá tiền nổi bật): `16px` / Bold 700 / Line height 1.3
  * `price-old` (Giá gốc gạch ngang): `13px` / Regular 400 / Gạch ngang (line-through)
  * `button-lg` (Nút kêu gọi chính): `16px` / Semibold 600 / Line height 1.25

### 2.3 Bo góc (Border Radius) & Đổ bóng (Elevation)
* **Bo góc mềm mại (Airbnb style):**
  * `rounded-sm`: `8px` (Cho các nút bấm nhỏ, ô nhập liệu)
  * `rounded-md`: `12px` (Góc bo chuẩn hệ thống: Card món ăn, ô tìm kiếm, modal)
  * `rounded-lg`: `16px` (Cho banner slider quảng cáo)
  * `rounded-pill`: `100px` (Dành cho Category Chip và bộ tăng giảm số lượng)
  * `rounded-full`: `9999px` (Dành cho nút tròn "Thêm+" hoặc badge giỏ hàng)
* **Đổ bóng tinh tế (Vercel style):**
  * `shadow-card`: `0px 2px 8px rgba(0, 0, 0, 0.06), 0px 0px 1px rgba(0, 0, 0, 0.08)` (Card ở trạng thái tĩnh)
  * `shadow-card-hover`: `0px 8px 24px rgba(0, 0, 0, 0.10), 0px 0px 1px rgba(0, 0, 0, 0.08)` (Card khi hover)
  * `shadow-nav`: `0px -2px 12px rgba(0, 0, 0, 0.08)` (Dành cho Bottom Navigation dưới đáy di động)
  * `shadow-modal`: `0px 16px 48px rgba(0, 0, 0, 0.16), 0px 0px 1px rgba(0, 0, 0, 0.1)` (Dành cho Modal popup và Toast)

---

## 🗺️ 3. Sơ đồ trang & Cơ sở dữ liệu tương thích

### 3.1 Sơ đồ di chuyển (User Flow)
```text
[Trang chủ (?page=homepage)] 
   │
   ├──► [Thực đơn (?page=food-listing)]
   │       │
   │       └──► [Chi tiết món (?page=food-detail&id=*)]
   │               │
   │               └──► (Thêm vào giỏ) ──┐
   │                                     ▼
   └──► [Giỏ hàng (?page=cart)] ◄────────┘
           │
           └──► (Đặt hàng) ──► [Trang Thanh toán (Checkout)]
```

### 3.2 Quy tắc đồng bộ dữ liệu (Database to UI rules)
Món ăn được hiển thị trên giao diện theo logic CSDL như sau:
1. **Ẩn món ăn:** Nếu cột `is_deleted = 1` trong bảng `foods` hoặc danh mục thuộc `categories` có `is_deleted = 1` -> Không render lên giao diện.
2. **Trạng thái HOT:** Nếu `is_hot = 1` -> Render nhãn dán "HOT 🔥" góc trên bên trái ảnh.
3. **Trạng thái SALE:** Nếu `is_sale = 1` -> Render nhãn dán giảm giá "-{discount_percent}%" góc trên bên phải ảnh. Đồng thời, hiển thị hai mức giá: giá mới nổi bật và giá gốc gạch ngang (`price` cũ).
4. **Trạng thái Tạm hết hàng:** Nếu `is_available = 0`:
   * Chuyển ảnh món ăn sang đen trắng (`filter: grayscale(100%)`) và mờ (`opacity: 0.5`).
   * Ẩn nút tròn "Thêm +".
   * Hiển thị nhãn xám tĩnh ghi chữ **"Tạm hết hàng"** ở đáy card.

---

## 📱 4. Đặc tả chi tiết 4 màn hình cốt lõi

### 4.1 Trang chủ (`homepage.php`)
* **Top Bar (56px, Sticky):** Logo bên trái, Icon Giỏ hàng + Badge đỏ hiển thị số lượng món bên phải.
* **Banner Slider (180px di động / 280px máy tính):** Tự động trượt sau 5 giây, các góc bo tròn 16px, chứa hình ảnh chương trình ưu đãi nổi bật.
* **Danh mục món ăn (Category Strip):** Thanh trượt ngang (Overflow-x scroll không hiện thanh cuộn), chứa các nút hình viên thuốc (Pill chip) đại diện cho các danh mục (🍗 Gà Rán, 🍕 Pizza, 🧋 Trà Sữa...). Nút kích hoạt có nền cam `#FF6600` và chữ trắng.
* **Món ăn nổi bật (Featured Foods):** Grid 2 cột trên di động, 4 cột trên máy tính. Mỗi card hiển thị ảnh món, tên, giá tiền, nhãn HOT/SALE và nút thêm nhanh dạng tròn.

```text
┌─────────────────────────────────────────┐
│ [Logo YumGO]              [🛒 Giỏ hàng] │ ← Top Bar (Sticky, 56px)
├─────────────────────────────────────────┤
│                                         │
│    ╔═══════════════════════════════╗     │
│    ║   🔥 KHUYẾN MÃI HÔM NAY       ║     │ ← Banner Slider (rounded-lg: 16px)
│    ╚═══════════════════════════════╝     │
│                                         │
│  Danh mục món ăn                        │ ← display-md
│  (🍗 Gà Rán) (🍕 Pizza) (🧋 Trà Sữa)   │ ← Category Strip (Scroll ngang)
│                                         │
│  Món ăn nổi bật 🔥         Xem tất cả   │ ← Section title + Link
│  ┌──────────┐  ┌──────────┐             │
│  │ [HOT]    │  │ [-15%]   │             │ ← Food cards grid (2 cột di động)
│  │ [ẢNH MÓN]│  │ [ẢNH MÓN]│             │   Tỷ lệ ảnh 4:3
│  │ Burger Bò│  │ Pizza    │             │
│  │ 55.000đ ⊕│  │ 89.000đ ⊕│             │ ← Nút tròn cam ⊕ để thêm vào giỏ
│  └──────────┘  └──────────┘             │
└─────────────────────────────────────────┘
```

### 4.2 Trang danh sách món (`food-listing.php`)
* **Thanh tìm kiếm (Search Bar):** Chiều cao 48px, bo góc 12px, có biểu tượng kính lúp bên trái và nút xóa nhanh nội dung "✕". Debounce tìm kiếm 300ms.
* **Nút bộ lọc (Filter Button):** Nằm cạnh thanh tìm kiếm, kích thước 48x48px, bo góc 12px để người dùng cấu hình lọc nâng cao.
* **Phân trang (Pagination):** Chuỗi nút hình vuông bo góc nhẹ 8px (`rounded-sm`). Trang hiện tại hiển thị nền màu cam nổi bật.

### 4.3 Trang chi tiết món (`food-detail.php`)
* **Ảnh bìa lớn (Hero Image):** Chiều cao tối đa 320px trên di động, hiển thị tràn màn hình (Full-bleed), các nhãn dán HOT/SALE đè trực tiếp lên ảnh.
* **Bộ chọn số lượng (Quantity Control):** Thiết kế dạng viên thuốc bo tròn tròn trịa. Nút trừ `[-]` và cộng `[+]` màu trắng bao bọc số lượng ở giữa.
* **Nút CTA chính (Sticky CTA):** Nút "Thêm vào giỏ — {Tổng tiền}" màu cam lớn, cố định dưới đáy màn hình di động giúp khách hàng dễ dàng thao tác bằng một ngón tay cái.

### 4.4 Trang giỏ hàng (`cart.php`)
* **Danh sách món ăn:** Từng dòng món ăn có ảnh nhỏ 80x80px bo góc 12px, tên món, đơn giá, bộ tăng giảm số lượng viên thuốc và nút xóa "✕" bên góc phải.
* **Tạm tính giỏ hàng:** Cố định phía trên thanh điều hướng dưới. Hiển thị tổng số lượng và số tiền tạm tính.
* **Nút Thanh toán:** Chiều cao 52px, nền cam rực rỡ, bo góc 12px đi kèm mũi tên biểu thị chuyển tiếp sang trang thanh toán.

---

## ⚠️ 5. Xử lý trạng thái trống (Empty States)

Mỗi trạng thái trống bắt buộc phải hiển thị tối giản, thẩm mỹ, gồm: **Icon minh họa lớn (opacity 0.8) + Tiêu đề ngắn gọn + Mô tả + Nút hành động chính (CTA)**.

### 5.1 Giỏ hàng trống (Empty Cart)
* **Icon:** 🛒 Giỏ hàng rỗng nét mảnh (120x120px).
* **Tiêu đề:** "Giỏ hàng của bạn đang trống!"
* **Mô tả:** "Hãy tham khảo thực đơn và lựa chọn những món ăn ngon lành từ YumGO nhé."
* **CTA:** Nút "Khám phá thực đơn" (Chuyển tiếp đến `food-listing.php`).

### 5.2 Không có món ăn nào (No Foods)
* **Icon:** 🍽️ Đĩa ăn và dĩa thìa trống.
* **Tiêu đề:** "Chưa có món ăn nào!"
* **Mô tả:** "Hệ thống đang cập nhật thực đơn mới. Vui lòng quay lại sau."
* **CTA:** Nút "Quay lại trang chủ" (Chuyển tiếp đến `homepage.php`).

### 5.3 Không tìm thấy kết quả tìm kiếm (No Search Result)
* **Icon:** 🔍 Kính lúp kết hợp dấu hỏi chấm.
* **Tiêu đề:** "Không tìm thấy kết quả!"
* **Mô tả:** "Chúng tôi không tìm thấy món ăn nào khớp với từ khóa tìm kiếm của bạn. Thử từ khóa khác nhé."
* **CTA:** Nút "Xem tất cả món ăn" (Reload lại trang danh sách món không có bộ lọc từ khóa).

---

## 📏 6. Quy chuẩn Responsive & Tương tác

### 6.1 Các điểm ngắt (Responsive Breakpoints)
* **Di động (Mobile < 576px):** Cấu hình mặc định. Grid hiển thị 2 cột món ăn để tiết kiệm không gian. Phải luôn hiển thị Bottom Navigation cố định đáy màn hình (`position: fixed; bottom: 0`).
* **Máy tính bảng (Tablet 768px - 991px):** Grid món ăn chuyển sang 3 cột. Thanh tìm kiếm mở rộng sang bề ngang.
* **Máy tính để bàn (Desktop ≥ 992px):** Ẩn hoàn toàn thanh Bottom Navigation dưới đáy. Chuyển sang thanh điều hướng phía trên đầy đủ link. Grid món ăn mở rộng sang 4 cột (`gap: 20px`). Bọc toàn bộ nội dung trong một container có độ rộng tối đa `1200px` căn giữa màn hình.

### 6.2 Chuyển động & Phản hồi (Micro-Interactions)
* **Transition mặc định:**
  ```css
  * {
    transition: all 200ms cubic-bezier(0.4, 0, 0.2, 1);
  }
  ```
* **Hover Card:** Thẻ món ăn bay nhẹ lên trên (`transform: translateY(-2px)`) và nâng cấp bóng mờ từ `shadow-card` sang `shadow-card-hover`.
* **Click Add Button:** Nút thêm món `(+)` co lại rồi phồng ra nhẹ để phản hồi vật lý cho người dùng (`scale(1) -> scale(0.85) -> scale(1.1) -> scale(1)`).
* **Toast Notification:** Slide từ dưới lên và mờ dần (Fade in) khi có món ăn mới được thêm thành công vào giỏ hàng, tự ẩn sau 3 giây.

---

## 🛠️ 7. Hướng dẫn dành cho Agent Thiết kế UI/UX

Khi bạn nhận vai trò thiết kế hoặc viết code Frontend cho dự án YumGO, hãy tuân thủ nghiêm ngặt các hướng dẫn sau:

1. **Khởi tạo CSS toàn cục:** Luôn đặt các biến CSS ở `:root` (như mục 2.1) trong tệp CSS chính (ví dụ: `index.css` hoặc `style.css`).
2. **Sử dụng tiện ích Bootstrap 5 kết hợp Custom CSS:** Tránh dùng các góc nhọn. Sử dụng `.rounded-3` (bo 12px) cho card và input. Nút bấm dạng thuốc dùng `.rounded-pill`. Nút thêm tròn dùng `.rounded-circle`.
3. **Tuyệt đối không dùng ảnh Placeholder xám xịt:** Nếu không có ảnh thực tế, hãy dựng một thẻ div chứa Gradient cam nhẹ (`#FFF0E6` sang `#FFD4B3`) kèm emoji món ăn lớn ở chính giữa làm ảnh đại diện tạm thời.
4. **Bảo mật giá cả từ Backend:** Khi hiển thị giá tiền trên giao diện, có thể định dạng đẹp bằng JavaScript hoặc PHP (`number_format`). Tuy nhiên, khi gửi sự kiện thêm vào giỏ hàng lên hệ thống, chỉ gửi đi `id` món ăn và `quantity` (số lượng). Cấm gửi thông tin giá bán từ Frontend.
5. **Giới hạn màu sắc:** Chỉ sử dụng màu Cam làm màu nhấn chính. Không tùy tiện thêm các màu sắc lạ khác như xanh dương, tím vào giao diện để tránh làm loãng thương hiệu.

---

## Checklist Kiểm định Giao diện (UX/UI Verification Checklist)

- [ ] Trọng lượng font chữ hiển thị cao nhất là 600 (Semibold) đối với tất cả tiêu đề (Không dùng Bold 700/800 quá nặng nề trừ Hero Banner).
- [ ] Tất cả các card món ăn, ô input, nút bấm chính đều được bo góc `12px` (hoặc `.rounded-3` trong Bootstrap).
- [ ] Không có góc cứng nhọn (0px radius) xuất hiện trên bất kỳ phần tử tương tác nào.
- [ ] Ảnh món ăn có thuộc tính `object-fit: cover` và không bị méo mó.
- [ ] Trạng thái tạm hết hàng làm mờ ảnh `grayscale` 100% kèm nhãn xám và ẩn hoàn toàn nút thêm món.
- [ ] Trên thiết bị di động, thanh Bottom Navigation hiển thị cố định ở đáy, chiều cao đúng 64px, và các vùng nhấn tương tác tối thiểu đạt 48x48px.
- [ ] Mọi hoạt ảnh hover card nâng lên nhẹ nhàng (translateY -2px) kèm shadow mịn màng.
- [ ] Các trang empty states (Giỏ hàng trống, Không có món, Tìm kiếm trống) đều có đủ icon lớn, tiêu đề, mô tả và nút CTA dẫn đường.
