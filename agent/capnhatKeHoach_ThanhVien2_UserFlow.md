# Ke hoach cong viec - Thanh vien 2: User Website / User Flow

## 1. Tong quan phan viec

Thanh vien 2 phu trach phan website danh cho khach hang cua YumGO. Phan nay gom giao dien nguoi dung, truy van du lieu hien thi mon an, tim kiem, loc, chi tiet mon, gio hang bang PHP Session va responsive mobile-first.

Pham vi chinh:

- Homepage
- Food listing
- Search va filter category
- Food detail
- Cart system
- Mobile responsive UI
- Empty states cho cac man hinh user

Cong nghe can dung theo dac ta:

- PHP 8+
- PDO Prepared Statement
- MySQL / MariaDB
- HTML5, CSS3, Bootstrap 5
- JavaScript co ban
- Bootstrap Icons
- PHP Session cho gio hang

## 2. Nguyen tac lam viec bat buoc

Khi code phan thanh vien 2, phai tuan thu cac quy tac chung cua du an:

- Khong tu y doi database schema.
- Khong doi ten bang, ten cot, status hoac folder structure.
- Khong doi route naming khi chua thong nhat.
- Khong redesign layout chung khi chua bao team.
- Khong hardcode DB credentials, BASE_URL, upload path.
- Tat ca query database phai dung PDO Prepared Statement.
- Tat ca output text phai dung `htmlspecialchars()` de tranh XSS.
- Tat ca input tu request phai `trim()` va validate.
- Tien va subtotal chi duoc tinh lai o backend, khong tin frontend.

## 3. Ranh gioi cong viec voi cac thanh vien khac

### 3.1. Voi thanh vien 1 - Core system, database, auth, config

Thanh vien 1 phu trach:

- Database schema va file `/database/yumgo.sql`
- Config chung trong `/config/config.php`
- Ket noi database trong `/includes/database.php`
- Helper dung chung
- Auth admin
- Folder structure chung
- Final UI consistency va merge cuoi

Thanh vien 2 can tranh:

- Khong tao schema rieng hoac sua cau truc bang `categories`, `foods`, `orders`, `order_items`, `vouchers`.
- Khong sua file config/database neu chi can dung lai ket noi.
- Khong thay doi helper chung neu khong thong nhat.
- Khong sua auth admin.
- Neu can helper moi cho user UI, nen dat ten ro rang va bao TV1 truoc khi dua vao shared helper.

Thanh vien 2 duoc lam:

- Goi model/query de doc `categories` va `foods`.
- Tao cac file view/controller/model lien quan den user flow.
- De xuat voi TV1 neu can constant hoac helper chung.

### 3.2. Voi thanh vien 3 - Checkout, order, voucher

Thanh vien 3 phu trach:

- Checkout form
- Validate thong tin dat hang
- Tao `orders` va `order_items`
- Voucher validation va discount
- Order tracking
- Order history
- Edit order
- Cancel order
- Export PDF invoice
- Transaction checkout

Thanh vien 2 can tranh:

- Khong insert order.
- Khong insert order_items.
- Khong xu ly voucher that.
- Khong tinh discount.
- Khong viet logic checkout transaction.
- Khong sua status order.
- Khong lam trang order tracking/history tru khi duoc phan cong them.

Thanh vien 2 duoc lam:

- Tao nut/link tu cart sang trang checkout cua TV3.
- Chuan bi cart session de TV3 co the doc du lieu.
- Hien thi subtotal tam thoi trong cart, nhung phai ghi ro backend checkout se tinh lai.
- Validate quantity va availability khi add/update cart.

### 3.3. Voi thanh vien 4 - Admin va shipper

Thanh vien 4 phu trach:

- Dashboard admin
- Category CRUD
- Food CRUD
- Voucher CRUD
- Order management
- Shipper management
- Delivery Board
- Shipper accept/complete delivery

Thanh vien 2 can tranh:

- Khong tao/sua trang admin CRUD.
- Khong upload image mon an trong user flow.
- Khong sua available, HOT, SALE cua mon.
- Khong lam dashboard/order management.
- Khong lam shipper flow.

Thanh vien 2 duoc lam:

- Doc cac truong `is_hot`, `is_sale`, `discount_percent`, `is_available` de hien thi badge/trang thai.
- An mon bi soft delete hoac category bi soft delete khoi phia user.
- Khong cho add cart neu `is_available = 0`.

## 4. Cau truc file de xuat cho thanh vien 2

Can theo folder structure da chot:

```text
/controllers
  UserHomeController.php
  FoodController.php
  CartController.php

/models
  Category.php
  Food.php

/views
  /user
    home.php
    foods.php
    food-detail.php
    cart.php
    partials/
      food-card.php
      empty-state.php

/assets
  /css
    user.css
  /js
    cart.js
```

Luu y:

- Neu TV1 da tao naming khac thi phai theo naming cua TV1, khong tu tao style rieng.
- Neu team dung routing `index.php?page=...`, cac route user nen thong nhat dang:
  - `index.php?page=home`
  - `index.php?page=foods`
  - `index.php?page=food-detail&id=1`
  - `index.php?page=cart`

## 5. Ke hoach tien trinh chi tiet

### Giai doan 0 - Cho nen tang tu thanh vien 1

Muc tieu: dam bao co du nen tang truoc khi code module rieng.

Viec can kiem tra:

- Co file `/config/config.php`.
- Co file `/includes/database.php`.
- Co database import duoc.
- Cac bang `categories` va `foods` co dung cot theo dac ta.
- Co sample data toi thieu de test homepage/listing/detail.
- Co folder `/uploads/foods/` va `/uploads/banners/`.
- Co `BASE_URL` hoac cach build URL thong nhat.

Ket qua dau ra:

- Xac nhan duoc can doc categories/foods.
- Biet dung bien ket noi PDO nao.
- Biet route chung cua project.

### Giai doan 1 - Tao model doc du lieu

Muc tieu: tao lop/function truy van du lieu cho categories va foods.

Viec can lam:

- Viet ham lay danh sach category chua bi xoa:
  - `is_deleted = 0`
- Viet ham lay danh sach mon an chua bi xoa:
  - `foods.is_deleted = 0`
  - category lien quan khong bi xoa
- Viet ham lay featured foods:
  - uu tien `is_hot = 1`
  - chi lay mon `is_available = 1` neu can hien thi noi bat
- Viet ham search theo ten mon:
  - case-insensitive
  - partial match
- Viet ham filter theo category.
- Viet ham pagination.
- Viet ham lay chi tiet mon theo `id`.

Luu y ky thuat:

- Dung Prepared Statement cho search/filter/id.
- Khong noi chuoi SQL truc tiep voi input nguoi dung.
- Validate `id` va `category_id` la so nguyen duong.
- Search keyword phai `trim()`.
- Khong hien thi food/category bi soft delete.

Ket qua dau ra:

- Model hoat dong doc du lieu dung rule.
- Co the test rieng tung query truoc khi gan UI.

### Giai doan 2 - Homepage

Muc tieu: tao trang chu user co tinh gioi thieu va dieu huong nhanh.

Thanh phan can co:

- Banner.
- Danh sach categories.
- Featured foods.
- Nut xem tat ca mon.
- Responsive mobile/tablet/desktop.

Checklist UI:

- Banner khong bi vo layout tren mobile.
- Category co the scroll hoac wrap hop ly.
- Food card co anh, ten, gia, badge neu co.
- Neu khong co featured foods thi hien empty state.
- Khong dung text qua dai lam tran card.

Ket qua dau ra:

- Trang `home` hien dung du lieu.
- Mobile <768px xem tot.
- Desktop >=992px co grid gon gang.

### Giai doan 3 - Food listing, search, filter, pagination

Muc tieu: tao trang danh sach mon an day du tinh nang tim kiem va loc.

Thanh phan can co:

- Grid/list mon an.
- Search theo ten.
- Filter category.
- Pagination.
- HOT badge.
- SALE badge.
- Available/unavailable status.
- Empty state khi khong co ket qua.

Business rules:

- Search partial match, case-insensitive.
- Category filter chi dung category ton tai va chua bi xoa.
- Mon `is_deleted = 1` khong hien voi user.
- Mon `is_available = 0` van co the hien thi neu team thong nhat, nhung phai hien "Currently Unavailable" va khong cho add cart.

Luu y khong lan viec:

- Khong tao/sua category o day, do la phan admin cua TV4.
- Khong sua cot HOT/SALE/available, chi doc va hien thi.

Ket qua dau ra:

- URL filter/search giu duoc state khi chuyen trang.
- Pagination khong mat keyword/category.
- Khong loi khi search rong.

### Giai doan 4 - Food detail

Muc tieu: tao trang chi tiet mon an va nut add to cart.

Thanh phan can co:

- Anh mon an lon.
- Ten mon.
- Mo ta.
- Gia.
- Badge HOT/SALE neu co.
- Trang thai available.
- Form chon quantity.
- Nut add to cart.

Business rules:

- `id` phai validate la so nguyen duong.
- Neu food khong ton tai, bi xoa, hoac category bi xoa thi hien empty/not found state.
- Neu `is_available = 0` thi disable add to cart.
- Quantity mac dinh la 1.
- Quantity phai > 0.

Luu y bao mat:

- Escape output ten mon, mo ta.
- Khong tin price gui tu frontend.
- Form add cart chi nen gui `food_id` va `quantity`, khong gui price/subtotal de backend tin theo.

Ket qua dau ra:

- Trang detail dung rule va khong add duoc mon unavailable.

### Giai doan 5 - Cart system bang PHP Session

Muc tieu: tao gio hang hoat dong day du bang session.

Chuc nang can co:

- Add cart.
- Remove item.
- Update quantity.
- Hien danh sach item trong cart.
- Hien subtotal.
- Empty cart state.
- Link sang checkout cua TV3.

Cau truc session de xuat:

```php
$_SESSION['cart'] = [
    food_id => [
        'food_id' => food_id,
        'quantity' => quantity
    ]
];
```

Khong nen luu:

- Gia lam nguon tin chinh.
- Subtotal lam nguon tin chinh.
- Ten mon/anh lam nguon tin chinh neu co the query lai.

Business rules bat buoc:

- Khi add/update cart, backend phai query lai food.
- Validate food ton tai.
- Validate food chua bi xoa.
- Validate food con available.
- Validate quantity > 0 va la so.
- Khi hien subtotal, backend query lai price hien tai tu database.
- Khong tin hidden input price/subtotal.

Luu y phoi hop voi TV3:

- Cart chi chuan bi du lieu cho checkout.
- Checkout cua TV3 phai query lai price, availability, voucher condition.
- Neu TV3 can format session khac, thong nhat som de tranh sua lai nhieu.

Ket qua dau ra:

- Cart them/sua/xoa duoc.
- Quantity cap nhat dung.
- Subtotal hien dung theo gia hien tai.
- Empty cart hien dep va ro.

### Giai doan 6 - Responsive mobile UI

Muc tieu: dam bao user flow dung mobile-first.

Thanh phan can co:

- Mobile navbar.
- Bottom navigation.
- Food cards responsive.
- Cart table/list responsive.
- Nut bam touch-friendly.
- Spacing phu hop mobile.

Breakpoint theo dac ta:

- Mobile: `<768px`
- Tablet: `768px - 991px`
- Desktop: `>=992px`

Checklist:

- Khong co horizontal scroll khong mong muon.
- Anh mon an khong bi meo.
- Nut add/update/remove bam duoc tren mobile.
- Bottom navigation khong che noi dung quan trong.
- Cart tren mobile nen dung list/card thay vi bang rong neu bang bi tran.

Ket qua dau ra:

- Test duoc tren mobile, tablet, desktop.

### Giai doan 7 - Empty states, loading states, toast

Muc tieu: hoan thien trai nghiem nguoi dung.

Empty states bat buoc:

- No foods.
- Empty cart.
- No search result.

Loading/toast can co:

- Toast add cart success.
- Loading/disable button khi submit add/update cart neu co JS.
- Thong bao khi mon unavailable.
- Thong bao khi quantity khong hop le.

Luu y:

- Neu toast style chung do TV1 quy dinh, dung lai style chung.
- Khong tao design he thong thong bao rieng lam lech UI.

Ket qua dau ra:

- User khong gap man hinh trong vo nghia.
- Feedback sau thao tac ro rang.

### Giai doan 8 - Test module thanh vien 2

Test functional:

- Homepage hien banner/categories/featured foods.
- Food listing hien dung mon.
- Search "bur" tim duoc Burger neu co data.
- Filter category hoat dong.
- Pagination giu search/filter.
- Food detail hien dung theo id.
- Add cart thanh cong voi mon available.
- Khong add duoc mon unavailable.
- Update quantity thanh cong.
- Remove item thanh cong.
- Empty cart hien dung.

Test security/validation:

- Search keyword co ky tu dac biet khong lam loi SQL.
- `id=abc` khong crash.
- `quantity=-1`, `quantity=0`, `quantity=abc` bi chan.
- Khong co price/subtotal tu frontend duoc tin lam nguon chinh.
- Output ten/mo ta duoc escape.

Test responsive:

- Mobile <768px.
- Tablet 768-991px.
- Desktop >=992px.

Test integration:

- Link checkout dung route cua TV3.
- Cart session TV3 doc duoc.
- UI khong pha layout chung cua TV1.
- Food HOT/SALE/available tu admin TV4 cap nhat thi user hien dung.

## 6. Thu tu uu tien lam viec

Nen lam theo thu tu sau de giam rui ro:

1. Doc database schema va route chung.
2. Tao model/query cho category va food.
3. Lam homepage.
4. Lam food listing, search, filter.
5. Lam food detail.
6. Lam add cart.
7. Lam cart page, update quantity, remove item.
8. Lam responsive mobile.
9. Lam empty states/toast/loading.
10. Test va sua loi.
11. Ban giao format cart session va route checkout cho TV3.

## 7. Cac diem can thong nhat som voi team

Can hoi/confirm voi team truoc khi code sau:

- Route chinh xac cua user pages la gi.
- Format chung cua layout header/footer.
- Ten file controller/model/view chot cuoi.
- Format cart session de TV3 doc khi checkout.
- Co hien mon unavailable trong listing khong, hay an hoan toan.
- Cong thuc hien gia SALE:
  - hien gia goc va gia sau giam, hay chi hien badge SALE.
- Banner lay tu database hay dung anh tinh trong `/uploads/banners/`.
- Style toast/loading dung chung cua TV1 hay tu lam rieng.

## 8. Quy tac commit va branch

Branch nen dung:

```text
feature/member2-user
```

Commit message goi y:

```text
feat: add user homepage
feat: add food listing search filter
feat: add food detail page
feat: implement session cart
fix: validate cart quantity
style: improve mobile user pages
```

Truoc khi push:

- Pull code moi tu dev.
- Test local.
- Kiem tra khong sua nham file cua TV1/TV3/TV4.
- Commit ro tung nhom chuc nang.
- Khong push truc tiep len `main`.

## 9. Checklist ban giao cho thanh vien 2

Khi hoan thanh, thanh vien 2 can ban giao:

- Danh sach file da tao/sua.
- Route user pages.
- Cach cart session duoc luu.
- Cac query/model chinh.
- Cac case da test.
- Cac diem can TV3 dung khi checkout.
- Cac diem can TV1 review ve UI/layout chung.

Checklist hoan thanh:

- [x] Homepage hoat dong.
- [x] Category hien dung.
- [x] Featured foods hien dung.
- [x] Food listing hoat dong.
- [x] Search hoat dong.
- [x] Filter category hoat dong.
- [x] Pagination hoat dong.
- [x] HOT/SALE badge hien dung.
- [x] Food detail hoat dong.
- [x] Add cart hoat dong.
- [x] Update quantity hoat dong.
- [x] Remove item hoat dong.
- [x] Subtotal duoc tinh lai o backend.
- [x] Khong add duoc food unavailable.
- [x] Empty cart/no foods/no search result day du.
- [x] Responsive mobile/tablet/desktop dat yeu cau.
- [x] Khong sua lan phan checkout/order/voucher cua TV3.
- [x] Khong sua lan phan admin/shipper cua TV4.

## 9.5. Tien do nang cap AJAX & Security (Hoan thanh)

- [x] Khoi tao file `assets/js/cart.js` de fetch AJAX khong load lai trang.
- [x] Thiet lap CSS Toast Notification truot muot ma trong `assets/css/user.css`.
- [x] Nhúng container thong bao `#toast-container` vao `views/layouts/header.php`.
- [x] Cap nhat `controllers/CartController.php` phan biet va tra ve JSON khi nhan request AJAX.
- [x] Tich hop kiem tra bao mat nghiem ngat backend (chan so luong am, so luong chu, check is_available, check is_deleted).

## 9.6. Tien do tich hop Cong nghe moi (Composer, Dotenv, Alpine, SweetAlert2, GSAP) (Hoan thanh)

- [x] Tich hop Composer (`composer.json`) de quan ly cac goi thu vien PHP tap trung.
- [x] Bao mat thong tin database qua file `.env` va `.env.example`.
- [x] Tich hop autoload cua Composer va nap thu vien `Dotenv` trong `config/config.php`.
- [x] Nhúng CDN cua Alpine.js, SweetAlert2, va GSAP vao `views/layouts/header.php`.
- [x] Cap nhat `assets/js/cart.js` voi cac animation cua GSAP (hieu ung stagger load cho mon an va danh muc, transition slide mượt ma khi xoa mon) va SweetAlert2 (thay the confirm dialogue va he thong toast).

## 9.7. Tien do toi uu giao dien Mobile (Hoan thanh)

- [x] Thiet ke lai Menu Navigation tren Mobile chuyen sang dang Left Sidebar Drawer (bam mo rong/thu gon tu ben trai) bang Alpine.js trong `views/layouts/header.php`.
- [x] Loai bo hoan toan Bottom Menu duoi cung tren Mobile trong `views/layouts/footer.php` de lam sach khong gian va tranh che khuat noi dung.
- [x] Di chuyen Sticky CTA bar (Thanh them vao gio hang nhanh tren Mobile) bam sat duoi cung man hinh (`bottom: 0`) trong trang chi tiet mon an `views/user/food-detail.php`.
- [x] Toi uu hoa giao dien gio hang Mobile trong `views/user/cart.php` sang dang danh sach the (Card List) thay vi bang (table) de tranh tran ngang.

## 9.8. Tien do bo sung tinh nang nang cao (Hoan thanh)

- [x] Tich hop o Tim Kiem Nhanh (Quick Search Box) ngay trong Hero Banner kem cac tag goi y tu khoa hot (Gà rán, Pizza, Trà sữa) tai `views/user/home.php`.
- [x] Them Bento Grid "Tai sao chon YumGO" voi 3 cam ket noi bat (Giao nhanh, Ve sinh, Uu dai) ngay duoi Hero de tang do tin cay.
- [x] Bo sung phan goi y "Co the ban cung thich" (Related Foods) tai trang chi tiet mon an `views/user/food-detail.php` lay cac mon cung danh muc de tang kha nang upsell.

## 9.9. Quy trinh Pre-flight Check va Kiem dinh thiet ke cao cap (Hoan thanh)

- [x] **CTA Button Contrast Check:** Xác thực mọi nút bấm đều vượt qua kiểm tra độ tương phản WCAG AA (được tô màu cam chủ đạo `#FF6600` tương phản mạnh với chữ trắng `#FFFFFF`).
- [x] **Form Contrast Check:** Ô tìm kiếm nhanh và các form nhập liệu có màu chữ và viền rõ ràng trên nền canvas, vượt qua kiểm tra WCAG AA.
- [x] **Button Wrap Validation:** Xác thực tất cả các nút CTA ở chế độ hiển thị desktop nằm trên 1 dòng đơn, không bị rớt dòng làm hỏng hình dạng nút bấm.
- [x] **Viewport Stability Check:** Kiểm tra không sử dụng chiều cao tĩnh `h-screen` cho Hero di động để tránh bị lỗi giật giật trên trình duyệt Safari iOS, thay thế bằng `min-h-[100dvh]` hoàn hảo.
- [x] **Copy Register Alignment:** Rà soát lại toàn bộ văn bản và nhãn dán trên các màn hình để đảm bảo văn phong ẩm thực thống nhất, sinh động và không chứa từ ngữ dịch máy lủng củng.
- [x] **Reduced Motion Support:** Đảm bảo các micro-interactions (GSAP, CSS animations) hỗ trợ bộ lọc `@media (prefers-reduced-motion)` đối với thiết bị cần tiết kiệm tài nguyên.

## 9.10. Rebuild Trang chu Premium theo kich ban WOW 3 giay (Hoan thanh)

- [x] **Hero Premium 100vh:** Triển khai Hero Banner cấu trúc Split-screen chiêm trọn khung nhìn ban đầu, tích hợp form tìm kiếm lớn bo tròn, ảnh combo cinematic fastfood và các nhãn gợi ý tag.
- [x] **Glassmorphic Floating Cards:** Lập trình 4 thẻ nổi trôi lơ lửng (Rating, Speed, Free Ship, Orders today) co hiệu ứng nền kính mờ glassmorphism và shadow đa tầng.
- [x] **Stripe-style Trust Section:** Thiết lập 4 khối chỉ số lớn tối giản ngay dưới Hero tạo độ uy tín thuyết phục.
- [x] **Bento Grid Benefits:** Tùy chỉnh màu sắc nền đa dạng bento cho 3 thẻ cam kết (Giao hàng, Vệ sinh, Ưu đãi) thay cho nền xám thô.
- [x] **Customer Reviews Grid:** Tích hợp lưới đánh giá khách hàng (testimonials) 3 cột mượt mà, hỗ trợ render động từ controller.
- [x] **GSAP ScrollTrigger Staggers:** Đồng bộ hiệu ứng GSAP cho các thẻ món ăn và thẻ đánh giá khách hàng tự động trượt xuất hiện khi cuộn trang.
- [x] **Mid Page CTA Banner:** Bổ sung banner kích thích đặt món giảm giá 20% dạng gradient tối huyền ảo.

## 10. Rui ro can luu y

- Neu TV1 chua chot database, khong nen code query qua sau vi de sua lai.
- Neu format cart session khong thong nhat voi TV3, checkout se bi loi integration.
- Neu tin frontend price/subtotal, se sai rule bao mat va sai business logic.
- Neu khong loc `is_deleted`, user co the thay mon/category da xoa mem.
- Neu khong check `is_available`, user co the dat mon khong con ban.
- Neu route tu tao khac routing chung, khi merge se conflict.
- Neu CSS user ghi de qua rong, co the pha UI admin hoac layout chung.

## 11. Tom tat nhanh

Thanh vien 2 nen tap trung vao trai nghiem nguoi dung truoc checkout: xem mon, tim/loc mon, xem chi tiet va gio hang. Moi du lieu quan trong nhu price, availability, subtotal phai duoc backend query va tinh lai. Khong lam sang checkout/order/voucher cua thanh vien 3, khong lam admin/shipper cua thanh vien 4, khong sua core database/config/auth cua thanh vien 1 neu chua thong nhat.
