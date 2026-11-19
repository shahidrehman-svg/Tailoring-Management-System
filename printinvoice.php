<?php
require_once('function.php');
dbconnect();
session_start();

if (!is_user()) {
  redirect('index.php');
}

// Fetch the unique identifier (order_id in this case)
$id = $_GET["id"];  // Assuming you get the order ID from the URL

// Query to fetch the order details using prepared statement
$stmt = $pdo->prepare("SELECT * FROM `order` WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch customer using prepared statement (ADD THIS)
if ($order && isset($order['customer'])) {
    $stmt = $pdo->prepare("SELECT fullname, phonenumber, address FROM customer WHERE id = ?");
    $stmt->execute([$order['customer']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $customer = ['fullname' => 'Customer Not Found', 'phonenumber' => '', 'address' => ''];
}

function truncateText($text, $maxLength = 30) {
    if (strlen($text) > $maxLength) {
        return substr($text, 0, $maxLength - 3) . '...';
    }
    return $text;
}

// Pass the order ID to JavaScript


// Fetch the unique identifier (order_id in this case)
$id = $_GET["id"];  // Assuming you get the order ID from the URL

// Query to fetch the order details using prepared statement
$stmt = $pdo->prepare("SELECT * FROM `order` WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Pass the order ID to JavaScript
?>

<?php
$user = $_SESSION['username'];
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$user]);
$usid = $stmt->fetch(PDO::FETCH_ASSOC);
$uid = $usid['id'];
include('header.php');
?>
<style type="text/css" media="print">
  @page {
    size: auto;
    /* auto is the current printer page size */
    margin: 0mm;
    /* this affects the margin in the printer settings */
  }

  body {
    background-color: #FFFFFF;
    margin: 0px;
    /* the margin on the content before printing */
    padding: 20px;
  }
  

  b,
  strong {
    font-weight: 100 !important;
  }
  

  @media print {
    .printbtn {
      display: none;
      
    }
  }
  
  @media print {
  .print-watermark {
    -webkit-print-color-adjust: exact; /* Chrome, Safari */
    print-color-adjust: exact;         /* Firefox, Edge */
  }
}
  
</style>
<script>
  var orderId = <?php echo json_encode($order['id']); ?>;
  console.log("Order ID:", orderId);
</script>
<script>
  function printContent(el) {
    var restorepage = document.body.innerHTML;
    var printcontent = document.getElementById(el).innerHTML;
    document.body.innerHTML = printcontent;
    window.print();
    document.body.innerHTML = restorepage;
  }
  var uid = <?php echo json_encode($order); ?>;
  console.log("User ID:", uid);
 function mPrint(el) {
    var restorePage = document.body.innerHTML;
    var printContent = document.getElementById(el).innerHTML;
    
    // Create a temporary container
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = printContent;
    
    // Remove ALL buttons and print-related elements
    var buttons = tempDiv.querySelectorAll('.printbtn, .btn, a[href=""]');
    buttons.forEach(function(btn) {
        btn.parentNode.removeChild(btn);
    });
    
    // Also remove the pull-right container
    var pullRight = tempDiv.querySelectorAll('.pull-right');
    pullRight.forEach(function(el) {
        el.parentNode.removeChild(el);
    });
    
    // Set the body content WITHOUT any buttons
    document.body.innerHTML = tempDiv.innerHTML;
    
    // Hide divs without visible checkmarks
    document.querySelectorAll('.div').forEach(function(div) {
        var check = div.querySelector('.check');
        if (!check || check.style.display === 'none') {
            div.style.display = 'none';
        }
    });

    window.print();
    document.body.innerHTML = restorePage;
}



// Save state when checkbox clicked
document.querySelectorAll('.div').forEach(function(div) {
    div.addEventListener('click', function() {
        var check = div.querySelector('.check');
        var id = div.getAttribute('data-id');
        if (!check) return;

        if (check.style.display === 'none') {
            check.style.display = 'inline';
            check.style.color = 'green';
            localStorage.setItem('checkbox-' + id, 'tick');
        } else {
            check.style.display = 'none';
            localStorage.setItem('checkbox-' + id, 'none');
        }
    });
});

// Restore state on page load
window.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.div').forEach(function(div) {
        var id = div.getAttribute('data-id');
        var check = div.querySelector('.check');
        if (!check) return;
        var state = localStorage.getItem('checkbox-' + id);
        if (state === 'tick') {
            check.style.display = 'inline';
            check.style.color = 'green';
        } else {
            check.style.display = 'none';
        }
    });
});


</script>

<div class="pageheader">
  <h2><i class="fa fa-th-list"></i>Print Receipt</h2>
</div>


<div class="contentpanel" id="all-p">
  <div class="pull-right print-button-container">
    <!-- <a href="" onClick="printContent('ass')" class="btn btn-info printbtn"><i class="icon-print icon-large"></i> Print Order</a> -->
    <a href="" onClick="mPrint('all-p')" class="btn btn-info printbtn"><i class="icon-print icon-large"></i> Print Receipt</a>
  </div>
  <div id="ass" style="
    
    padding: 20px;
    margin: 0 auto;
    max-width: 700px;
    background: #fff;"><br />

<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); 
            opacity: 0.1; z-index: 0; pointer-events: none;">
    <img src="img/logo_pic.png" style="width: 400px; height: 400x; display: block;">
</div>
   <div style="margin-bottom: 8px; text-align: center;">
    <div style="display: flex; align-items: center; justify-content: center; gap: 180px;">
    <span style="
    font-size: 18px; 
    font-weight: bold; 
    color: transparent !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    text-shadow: none !important;
">
    LEFT TEXT
</span>
    <img src="img/logo_pic.png" style="width: 80px; height: 70px;">
    <!-- Original div (always visible) -->
<div class="div" data-id="gaddi-2" style="font-family: inherit; font-size: 16px; direction: rtl; text-align: center; width:80px; padding: 4px 3px; border: 1px solid #000000ff; border-radius: 4px;">
    <span class="check" style="font-family: Arial, sans-serif;">✔️</span> Verified
</div>

<!-- Just Verified div (only appears on print if original is unchecked) -->
<div class="fr-text" style="font-family: inherit; font-size: 20px; direction: rtl; text-align: center; width:110px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px; display: none;">
    Verified
</div>
</div>
    <h1 style="font-weight: 700; margin: 0; font-size: 24px; color: #000;">Bhatti Tailors & Fabrics</h1>
  <div style="display: flex; justify-content: space-between; align-items: center;">
<span>
    Name: 
    <?php
function englishToUrduName($englishName) {
    // آپ کے تمام ناموں کی مکمل ڈکشنری
    $namesDictionary = [
        // پچھلے نام
        'hashir' => 'حاشر',
        'humayun' => 'ہمایوں',
        'waseem' => 'وسیم',
        'ghafar' => 'غفار',
        'ghafoor' => 'غفور',
        'shoaib' => 'شعیب',
        'najm' => 'نجم',
        'din' => 'الدین',
        'najm u din' => 'نجم الدین',
        'najmuddin' => 'نجم الدین',
        'usama' => 'اسامہ',
        'kashif' => 'کاشف',
        'kabir' => 'کبیر',
        'masror' => 'مسرور',
        'zamurad' => 'زمرد',
        'asim' => 'عاصم',
        'mushtaq' => 'مشتاق',
        'asim mushtaq' => 'عاصم مشتاق',
        'usman' => 'عثمان',
        'arshid' => 'ارشد',
        'arshad' => 'ارشد',
        'abdul wahab' => 'عبدالوہاب',
        'dewan' => 'دیوان',
        'wahab' => 'وہاب',
        'arifeen' => 'عارفین',
        'wajahit' => 'وجاہت',
        'sadam' => 'صدام',
        'adeel' => 'عدیل',
        'muhtasim' => 'محتسیم',
        'arshid' => ' ارشد',
        'ali' => 'علی',
        'najaf' => 'نجف',
        'hassaan' => 'حسان',
        'ashiq' => 'عاشق',
        'asheq' => 'عشیق',
        'arshman' => 'عرشمان',
        'kadeem' => 'قدیم',
        'zamen' => 'ضامن',
        'zagheer' => 'ظہیر',
        'ramzan' => 'رمضان',
        'ali hassaan' => 'علی حسان',
        'aziz' => 'عزیز',
        'maiz' => 'معز',
        'fiaz' => 'فیاض',
        'jamshid' => 'جمشید',
        'rehman' => 'رحمان',
        'saif rehman' => 'سیف الرحمن',
        'tariq' => 'طارق',
        'ul' => 'ال',
        'aziz ul rehman' => 'عزیز الرحمان',
        'shehzad' => 'شہزاد',
        'feroz' => 'فیروز',
        'abdul ghafar' => 'عبدالغفار',
        'aslam' => 'اسلم',
        'sadique' => 'صدیق',
        'sadiq' => 'صادق',
        'shehzad aslam sadique' => 'شہزاد اسلم صدیق',
        'asif' => 'آصف',
        'husnain' => 'حسنین',
        'reyaz' => 'ریاض',
        'asif reyaz' => 'آصف ریاض',
        'tahir' => 'طاہر',
        'mehmood' => 'محمود',
        'tahir mehmood' => 'طاہر محمود',
        'ayan' => 'ایان',
        'khakan' => 'حاقان',
        'javed' => 'جاوید',
        'musakher' => 'مسخر',
        'mubarak baig' => 'مبارک بیگ',
        'm ayan javed' => ' ایان جاوید',
        'waje' => 'وجیح',
        'waje ur rehman' => 'وجیح الرحمان',
        'mlk' => 'ملک',
        'zeeshan' => 'ذیشان',
        'mlk zeeshan' => 'ملک ذیشان',
        'qari' => 'قاری',
        'naveed' => 'نوید',
        'esa' => 'عیسیٰ',
        'naqeeb' => 'نقیب',
        'qari naveed' => 'قاری نوید',
        'hassnain' => 'حسنین',
        'haider' => 'حیدر',
        'ali haider' => 'علی حیدر',
        'sheryar' => 'شیریار',
        'zulfiqar' => 'ذوالفقار',
        'kamran' => 'کامران',
        'kamran khan' => 'کامران خان',
        'ameen' => 'امین',
        'misam' => 'میثم',
        'meer' => 'میر',
        'bilal' => 'بلال',
        'bilal haider' => 'بلال حیدر',
        'imran' => 'عمران',
        'imran' => ' عمران',
        'banaras' => ' بنارس',
        'samsan' => 'ثمثن',
        'yousaf' => 'یوسف',
        'akram' => 'اکرم',
        'sany' => 'سنی',
        'jalal' => 'جلال',
        'fahsi' => 'فصیح',
        'waseem akram' => 'وسیم اکرم',
        'abdul' => 'عبدال',
        'abdul razaq' => 'عبدالرزاق',
        'khaliq' => 'خالق',
        'abdul khaliq' => 'عبدالخالق',
        'adil' => 'عادل',
        'yoel' => 'ئیول',
        'satti' => 'ستّی',
        'nikash' => 'نقاش',
        'shams u din' => 'شمس الدین',
        'adil satti' => 'عادل ستّی',
        'habib' => 'حبیب',
        'habib ur rehman' => 'حبیب الرحمان',
        'habib ul rehman' => ' حبیب الرحمن',
        'faisal' => 'فیصل',
        'faiq' => 'فائق',
        'bihlol' => 'بہلول',
        'mukhtyar' => 'مختیار',
        'niazi' => 'نیازی',
        'faisal niazi' => 'فیصل نیازی',
        'ammad' => 'عماد',
        'khuwaja' => 'خواجہ',
        'khursheed' => 'خورشید',
        'ammad khursheed' => 'عماد خورشید',
        'mubshir' => 'مبشر',
        'hassan' => 'حسن',
        'mubshir hassan' => 'مبشر حسن',
        'atif' => 'عاطف',
        'hussain' => 'حسین',
        'atif hussain' => 'عاطف حسین',
        'wahaj' => 'وہاج',
        'wahaj ameen' => 'وہاج امین',
        'ghulam' => 'غلام',
        'mustafa' => 'مصطفیٰ',
        'ghulam mustafa' => 'غلام مصطفیٰ',
        'ali khan' => 'علی خان',
        'sami' => 'سمیع',
        'mansib' => 'منصب',
        'roshaan' => 'روشان',
        'abdul sami' => 'عبدالسمیع',
        'sami ' => 'سمیع',
        'irfan' => 'عرفان',
        'taj' => 'تاج',
        'irfan taj' => 'عرفان تاج',
        'ghazenfer' => 'غضنفر',
        'faizan' => 'فیضان',
        'faizan taj' => 'فیضان تاج',
        'liyaqit' => 'لیاقت',
        'liyaqit ali' => 'لیاقت علی',
        'waheed' => 'وحید',
        'murad' => 'مراد',
        'waheed murad' => 'وحید مراد',
        'safeer' => 'سفیر',
        'haris' => 'حارث',
        'anwan' => 'عنوان',
        'momen' => 'مومن',
        'khabab' => 'خباب',
        'sana' => 'ثناء',
        'tanveer' => 'تنویر',
        'ahmed' => 'احمد',
        'tanveer ahmed' => 'تنویر احمد',
        'hamid' => 'حمید',
        'ullah' => 'اللہ',
        'hamid ullah khan' => 'حمید اللہ خان',
        'sharif' => 'شریف',
        'multan' => ' ملتان',
        'duraiz' => ' دریز',
        'waris' => ' وارث',
        'mughees' => ' مغیث',
        'muhaib' => ' محب',
        'taha' => 'طہٰ',
        'shamsher' => 'شمشیر',
        'taha' => ' طہٰ',
        'mufaser' => ' مفسر',
        'shemoon' => ' شیمون',
        'abubaker' => 'ابوبکر',
        'm abubaker' => 'محمد ابوبکر',
        'qasim' => 'قاسم',
        'mehdi' => 'مہدی',
        's qasim mehdi' => 'سید قاسم مہدی',
        'talib' => 'طالب',
        'talib hussain' => 'طالب حسین',
        'sheryar khan' => 'شیریار خان',
        'kashan' => 'کاشان',
        's kashan' => 'سید کاشان',
        'maisam' => 'میثم',
        'm maisam' => 'محمد میثم',
        'mujaab' => 'مجاب',
        'sherry' => 'شیری',
        'nouman' => 'نعمان',
        'nusrat' => 'نصرت',
        'sajawal' => 'سجاول',
        'zubairi' => ' زبیری',
        'chen' => 'چن',
        'shahid ul rehman' => 'شاہدالرحمن',
        'shahid ur rehman' => 'شاہدالرحمان',
        'nouman satti' => 'نعمان ستّی',
        'muqeet' => 'مُقیت',
        'abdul muqeet' => 'عبدالمقیت',
        'basit' => 'باسط',
        'basit ali' => 'باسط علی',
        'abdur' => 'عبدال',
        'huzaifa' => 'حذیفہ',
        'abdul muneeb' => 'عبدالمنیب',
        'zabi' => 'ذبیح',
        'jutt' => 'جٹ',
        'nazakit' => 'نزاکت',
        'jahanzeeb' => 'جہانزیب',
        'abdur rehman' => 'عبدالرحمان',
        'abdul rehman' => 'عبدالرحمن',
        'hassnain' => ' حسنین',
        'farukh' => ' فرخ',
        'sherjeel' => 'شرجیل',
        'shajahat' => 'شجاعت',
        's shajahat' => 'سید شجاعت',
        'zameer' => 'ضمیر',
        'zameer ul hassan' => 'ضمیر الحسن',
        'shah' => 'شاہ',
        'ali shah' => 'علی شاہ',
        'asim khan' => 'عاصم خان',
        'ayan khan' => 'ایان خان',
        'albar' => 'البار',
        'albar khan' => 'البار خان',
        'sbuhan' => 'سبحان',
        'shakeel' => 'شکیل',
        'shakeel ahmed' => 'شکیل احمد',
        'amir' => 'عامر',
        'ch' => 'سی ایچ',
        'imtiaz' => ' امتیاز',
        'autos' => 'آٹوز',
        'amir ch autos' => 'عامر سی ایچ آٹوز',
        'm qasim' => 'محمد قاسم',
        'shahbir' => 'شبیر',
        'shahbaz' => 'شہباز',
        'police' => 'پولیس',
        'ghulam shahbir' => 'غلام شبیر',
        'haseeb' => 'حسیب',
        'qamer zaman' => 'قمر زمان',
        'zaman' => ' زمان',
        'naimat' => ' نعمت',
        'zohaib' => 'زوہیب',
        'salman' => 'سلمان',
        'tuheed' => 'توحید',
        'ayaz' => 'ایاز',
        'tuheed ayaz' => 'توحید ایاز',
        'nouman khursheed' => 'نعمان خورشید',
        'syed' => 'سید',
        'affser' => 'افسر',
        'taqi' => 'تقی',
        'azmat' => 'عظمت',
        'mateen' => 'متین',
        'sudeer' => 'صدیر',
        'raqeeb' => 'رقیب',
        'abdul rafay' => 'عبدالررفع',
        'abbass' => 'عباس',
        'syed taqi abbass' => 'سید تقی عباس',
        'nouman khan' => 'نعمان خان',
        'muhammad' => 'محمد',
        'nabeel' => 'نبيل',
        'ibtihaj' => 'ابتہاج',
        'rahim' => 'رحیم',
        'm rahim abbas' => 'محمد رحیم عباس',
        'danish' => 'دانش',
        'nawaz' => 'نواز',
        'nawab' => 'نواب',
        'danish nawaz' => 'دانش نواز',
        'talal' => 'طلال',
        'm talal' => ' طلال',
        'kifayat' => 'کفایت',
        'kifayat allah' => 'کفایت اللہ',
        'mansoor' => 'منصور',
        'sultan' => 'سلطان',
        'mansoor sultan' => 'منصور سلطان',
        'zain' => 'زین',
        'tuqeer' => 'توقیر',
        'ammad' => 'عماد',
        'syed tuqeer abbass' => 'سید توقیر عباس',
        'ihtisham' => 'احتشام',
        'zubair' => 'زبیر',
        'ihtisham zubair' => 'احتشام زبیر',
        'waqar' => 'وقار',
        'waqar arshad' => 'وقار ارشد',
        'aman' => 'امان',
        'irshad' => 'ارشاد',
        'khubib' => 'حبیب',
        'sirmad' => 'سرمد',
        'aleem' => 'علیم',
        'younas' => 'یونس',
        'aman younas' => 'امان یونس',
        'baker' => 'بکر',
        'baaker' => 'باقر',
        'khuram' => 'خرم',
        'marwan' => 'مروان',
        'khuram abbass' => 'خرم عباس',
        'shehzad khan' => 'شہزاد خان',
        'nadeem' => 'ندیم',
        'dalazm' => 'دلازم',
        'qauom' => 'قیوم',
        'm qauom' => 'محمد قوم',
        'faizan' => 'فیضان',
        'faizan' => ' فیضان',
        'sardar' => ' سردار',
        'taimur' => 'تیمور',
        'taimur ' => 'تیمور ',
        'nisar' => 'نثار',
        'tasawer' => 'تصور',
        'iqbal' => 'اقبال',
        'tasawer iqbal' => 'تصور اقبال',
        'israr' => 'اسرار',
        'israr ahmed' => 'اسرار احمد',
        'girja' => 'گرجا',
        'asmer' => 'اثمر',
        'khalil' => 'حلیل',
        'haseeb girja' => 'حسیب گرجا',
        'asad' => 'اسد',
        'muzafer' => 'مظفر',
        'waqeel' => 'وکیل',
        'asad ali' => 'اسد علی',
        'sajad' => 'سجاد',
        'baker' => 'باقر',
        'sanan' => 'سنان',
        'zamair' => 'زمائر',
        'murtaza' => 'مرتضیٰ',
        'manseb' => 'منصب',
        'mukhtasim' => 'مختسم',
        'islam' => ' اسلام',
        'manzir islam' => 'منظر اسلام',
        'musadiq islam' => 'مصدق اسلام ',
        'hashir menhas' => 'حاشر منہاس',
        'sajad ahmed' => 'سجاد احمد',
        'mudasar' => 'مدثر',
        'rehmat' => 'رحمت',
        'serab' => 'سیراب',
        'ikram' => 'اکرم',
        'ikraam' => 'اکرام',
        'nicki' => 'نئکی',
        'hazrat' => 'حضرت',
        'maghaz' => 'معاز',
        'jafir' => 'جعفر',
        'lughari' => 'لغاری',
        'lasani' => 'لاثانی',
        'mudasar khan' => 'مدثر خان',
        'atta' => 'عطا',
        'atta u rehman' => 'عطاالرحمن ',
        'wajahat' => 'وجاہت',
        'syed wajahat ali' => 'سید وجاہت علی',
        'sarfaraz' => 'سرفراز',
        'afsar' => 'افسر',
        'raziq' => 'رازق',
        'khan afsar' => 'خان افسر',
        'saleem' => 'سلیم',
        'syed saleem shah' => 'سید سلیم شاہ',
        'mehsi' => 'مہسی',
        'zain mehsi' => 'زین مہسی',
        'numbar' => 'نمبر',
        'maqbool' => 'مقبول',
        'maqsood' => 'مقصود',
        'khusairam' => 'خسیرم',
        'tikidar' => 'ٹھکیدار',
        'dar' => 'دار',
        'daar' => 'ڈار',
        'yasir' => 'یاسر',
        'janhangir' => 'جہانگیر',
        'numbar dar yasir' => 'نمبر دار یاسر',
        'janhanzeeb' => 'جانہ زیب',
        'tayab' => 'طیب',
        'yaseen' => '	یٰسین',
        'yasseen' => '	یاسین',
        'kamil' => '	کمیل',
        'tayab shah' => 'طیب شاہ',
        'ahsan' => 'احسان',
        'farat' => 'فرت',
        'ahsen' => 'احسن',
        'shams ul haq' => 'شمس الحق',
        'luqman' => 'لقمان',
        'adalat' => 'عدالت',
        'abdul qadeer' => 'عبدالقدیر',
        'ahsan abbass' => 'احسن عباس',
        'hiader' => 'حیدر',
        'hiader khan' => 'حیدر خان',
        'ibrar' => 'ابرار',
        'mumtaz' => 'ممتاز',
        'umar' => 'عمر',
        'bhatt' => 'بٹ',
        'ahad' => 'احد',
        'dr' => 'ڈاکٹر',
        'aiaz' => 'عائز',
        'abdul basit' => 'عبدالباسط',
        'khayam' => 'خیام',
        'umar khayam' => 'عمر خیام',
        'addul' => 'عبدال',
        'majeed' => 'مجید',
        'addul majeed' => 'عبدالمجید',
        'ishfaq' => 'اشفاق',
        'qari ishfaq' => 'قاری اشفاق',
        'hassam' => 'حسام',
        'aftab' => 'افتاب',
        'abeer' => 'عبیر',
        'gujar' => 'گجر',
        'sabir' => 'صابر',
        'furqan' => 'فرقان',
        'hassam aftab' => 'حسام افتاب',
        'riyasat' => 'ریاست',
        'changaiz khan' => 'چنگیزخان',
        'ahsan ul haq' => 'احسان الحق',
        'arshad ul haq' => ' ارشادالحق',
        'riysat khan' => 'ریاست خان',
        'roban' => ' ربان',
        'saleem' => 'سلیم',
        'saleem' => ' سلیم',
        'wazir' => ' وزیر',
        'gull' => 'گل',
        'alyan' => 'عالیان',
        'ahmer' => 'احمر',
        'school' => 'سکول',
        'tehzeeb' => 'تہذیب',
        'butt' => ' بٹ',
        'baz' => 'باز',
        'm gull faraz' => 'گلفراز',
        'gull baz khan' => 'گل باز خان',
        'farhan' => 'فرحان',
        'farhat' => 'فرحت',
        'izaz' => 'اعزاز',
        'aoon' => 'عون',
        'jalees ul hassan' => 'جلیس الحسن',
        'farhad' => 'فرہاد',
        'hizar' => 'خضر',
        'yaman' => 'یامان',
        'janat' => 'جنت',
        'zareen' => 'زرین',
        'hayat' => 'حیات',
        'hasaan' => 'حسان',
        'lakan' => 'لاکان',
        'farhan lakan' => 'فرحان لاکان',
        'hanzala' => 'ہنزلہ',
        'riaz' => 'ریاض',
        'bhatti' => 'بھٹی',
        'riaz bhatti' => 'ریاض بھٹی',
        'turaiz' => 'طریز',
        'aftab' => 'افتاب',
        'aftab ahmed' => 'افتاب احمد',
        'pervaiz' => 'پرویز',
        'officer' => 'افیسر',
        'pervaiz hussain' => 'پرویز حسین',
        'abdin' => 'عابدین',
        'zain ul abdin' => 'زین العابدین',
        'zeeshan' => 'ذیشان',
        'ahmed zeeshan' => 'احمد ذیشان',
        'ghos' => 'غوث',
        'zafran' => 'زغفران',
        'munawer' => 'منور',
        'khasta' => 'خستہ',
        'shaheer' => 'شہیر',
        'hasam' => 'حسام',
        'ghos khan' => 'غوث خان',
        'raja' => 'راجہ',
        'mubeen' => 'مبین',
        'raja mubeen' => 'راجہ مبین',
        'naveed' => 'نوید',
        'm naveed' => 'محمد نوید',
        'shahzeb' => 'شہزیب',
        'muneeb' => 'منیب',
        'uzair asad' => 'اوزیر اسد',
        'timur' => 'تیمور',
        'malik' => 'مالک',
         'abdul malik' => 'عبدالمالک',
        'timur hussain' => 'تیمور حسین',
        'wajid' => 'واجد',
        'syed wajid hussain shah' => 'سید واجد حسین شاہ',
        
        // نئے شامل کردہ نام
        'adullah' => 'عداللہ',
        'ijaz' => 'اعجاز',
        'adullah ijaz' => 'عداللہ اجاز',
        'jawad' => 'جواد',
        'jawad khan' => 'جواد خان',
        'imran azam' => 'عمران اعظم',
        'azam' => ' اعظم',
        'saif' => 'سیف',
        'saif ali' => 'سیف علی',
        'dil' => 'دل',
        'nawaz' => 'نواز',
        'dil nawaz' => 'دل نواز',
        'mubashir' => 'مبشر',
        'bashir' => 'بشیر',
        'haizal' => 'ہیزیل',
        'anees ul hassan' => 'انیس الحسن',
        'mubashir bashir' => 'مبشر بشیر',
        'idress' => 'ادریس',
        'idress' => ' ادریس',
        'junaid' => 'جنید',
        'naik' => 'نیک',
        'basharat' => 'بشارت',
        'dastgir' => 'دستگیر',
        'muntazir' => 'منتظر',
        'sath ul hassan' => 'صط الحسن',
        'zahid' => 'زاہد',
        'syed zahid hussain shah' => 'سید زاہد حسین شاہ',
        'yasir' => 'یاسر',
        'syed yasir abbas' => 'سید یاسر عباس',
        'shahid' => 'شہید',
        'syed shahid hussain' => 'سید شہید حسین',
        'kashan' => 'کاشان',
        'haider' => 'حیدر',
        'syed kashan haider' => 'سید کاشان حیدر',
        'noorain' => 'نورعین',
        'noorain abbas' => 'نورعین عباس',
        'dilawar' => 'دلاور',
        'dilawar shah' => 'دلاور شاہ',
        'shakor' => 'شکور',
        'shahadit' => 'شہادت',
        'shahad' => 'شہاد',
        'huzaima' => 'حذیمہ',
        'abdul shakoor' => 'عبدالشکور',
        'namam haneef' => 'نمام حنیف',
        'haneef' => ' حنیف',
        'yasaya' => ' یسایا',
        'shakor abbas' => 'شکور عباس',
        'mujahid' => 'مجاہد',
        'mujahid hussain' => 'مجاہد حسین',
        'subtain' => 'سبطین',
        'subhan' => 'سبحان',
        'abad' => 'عباد',
        'rubas' => 'روباس',
        'bait' => 'بیت',
        'ishtiaq' => 'اشتیاق',
        'muna' => 'منا',
        'aurangzeb' => ' اورنگزیب',
        'mobile' => 'موبائل',
        'subtain mobile' => 'سبطین موبائل',
        'zia' => 'ضیاء',
        'arshad post office' => 'ارشد پوسٹ آفس',
        'gaon' => 'گاؤں',
        'shehzad gaon' => 'شہزاد گاؤں',
        'haroon' => 'ہارون',
        'bilawal' => 'بلاول',
        'bilal haroon' => 'بلال ہارون',
        'hassan zubair' => 'حسن زبیر',
        'haji' => 'حاجی',
        'amjid' => 'امجد',
        'usman haji amjid' => 'عثمان حاجی امجد',
        'jamail' => 'جمیل',
        'farooq' => 'فاروق',
        'jamail farooq' => 'جمیل فاروق',
        'munir' => 'منیر',
        'm munir' => 'محمد منیر',
        'hammad' => 'حماد',
        'syed hammad haider' => 'سید حماد حیدر',
        'syed hassan ali' => 'سید حسن علی',
        'sunny' => 'سنی',
        'jalil' => 'جلیل',
        'farman' => 'فرمان',
        'arman' => 'ارمان',
        'nadir' => 'نادر',
        'tabassum' => 'تبسم',
        'jamal' => 'جمال',
        'farman bhatti' => 'فرمان بھٹی',
        'rafay' => 'رفیع',
        'raafay' => ' رافع',
        'shahmeer' => ' شاہ میر',
        'akram' => 'اکرم',
        'saqib' => 'ثاقب',
        'saqlian' => 'ثقلین',
        'ishtiyaq' => 'اشتیاق',
        'akhnaf' => 'اخنف',
        'sherpaw' => 'شیرپاو',
        'akram' => ' اکرم',
        'talha' => 'طلحہ',
        'ashraf' => 'اشرف',
        'rasheed' => 'رشید',
        'qayum' => 'قیوم',
        'abdul qayum' => 'عبدالقیوم',
        'talat' => 'طلعت',
        'rafiq' => 'رفیق',
        'abdul khanan' => 'عبدالخنان',
        'mahaz' => 'محاز',
        'fazul rehman' => 'فضل الرحمن',
        'fuzail' => 'فضیل',
        'fazal naeem' => 'فضل نعیم',
        'fazal' => 'فضل ',
        'karim' => 'کریم',
        'jahan' => 'جہان',
        'azhan' => 'آذہان',
        'shafaghit' => 'شفاعت',
        'abdul naeem' => 'عبدالنعیم',
        'mehboob' => 'محبوب',
        'mehboob rehman' => 'محبوب الرحمن',
        'ali talha' => 'علی طلحہ',
        'rizwan' => 'رضوان',
        'mukhtar' => 'مختار',
        'afzal' => 'افضل',
        'rizwan' => ' رضوان',
        'aqeel' => 'عقیل',
        'hassan sajid' => 'حسن ساجد',
        'sajid' => ' ساجد',
        'mehmood sajid' => ' محمود ساجد',
        'aqeel ahmed' => 'عقیل احمد',
        'hasnat' => 'حسنات',
        'abdullah sajid' => 'عبداللہ ساجد',
        'ibrar hussain' => 'ابرار حسین',
        'abbas' => 'عباس',
        'm abbas' => 'محمد عباس',
        'sajid mehmood bhatti' => 'ساجد محمود بھٹی',
        'rukhsar' => 'رخسار',
        'rukhsar ahmed' => 'رخسار احمد',
        'malak' => 'ملک',
        'ateeb' => 'عطیب',
        'malak ateeb nawaz' => 'ملک عطیب نواز',
        'khafiz' => 'حافظ',
        'shams' => 'شمس',
        'sohail' => 'سہیل',
        'wasiyan' => 'وسیان',
        'ikhlaq' => 'اخلاق',
        'hafiz sohail' => 'حافظ سہیل',
        'rameez' => 'رمیز',
        'sufiyan' => 'سفیان',
        'saleh u din' => 'صلاح الدین',
        'chaudhry' => 'چودھری',
        'ch' => 'چودھری',
        'qafeel' => 'کفیل',
        'asjid' => 'اسجد',
        'farkhit' => 'فرحت',
        'sajad' => 'سجاد',
        'turaiz' => 'توریز',
        'raza' => 'رضا',
        'muzamil' => 'مزمل',
        'abdul haseeb' => 'عبدالحسیب',
        'chaudhry tuheed' => 'چودھری توحید',
        'naeem' => 'نعیم',
        'abdul mugheez' => 'عبدالمعز',
        'qamer' => 'قمر',
        'menhas' => ' منہاس',
        'hanan' => 'حنان',
        'fiaz ul rehman' => 'فیاض الرحمن',
        'qazi annas' => 'قاضی انس',
        'qazi' => 'قاضی ',
        'annas' => ' انس',
        'akber' => ' اکبر',
        'mehroz' => ' مہروز',
        'ibtasam' => ' ابتسام',
        'sidaque' => ' صدیق',
        'sidaque' => ' صدیق',
        'numberdar' => 'نمبردار',
        'nouman sadar ali' => 'نعمان صدر علی',
        'usman malak' => 'عثمان ملک',
        'tuqeer zaheer' => 'تقیر ظہیر',
        'waqas' => 'وقاص',
        'waqas' => ' وقاص',
        'zaiham' => ' ضیخم',
        'arsalan' => 'ارسلان',
        'zaheer' => 'ظہیر',
        'mujeeb ul rehman' => 'مجیب الرحمان',
        'zaheer ul din' => 'ظہیرالدین',
        'hassan' => ' حسن',
        'haider ali ishaq' => 'حیدر علی اسحاق',
        'hamza' => 'حمزہ',
        'jelani' => 'جیلانی',
        'hamza farooq' => 'حمزہ فاروق',
        'hafiz m hassan' => 'حافظ محمد حسن',
        'aftab khan' => 'افتاب خان',
        'khuram aftab' => 'خرم افتاب',
        'majid' => 'ماجد',
        'm majid' => 'محمد ماجد',
        'baloch' => 'بلوچ',
        'idress baloch' => 'ادریس بلوچ',
        'ilyas' => 'الیاس',
        'ahmed ilyas' => 'احمد الیاس',
        'ayub' => 'ایوب',
        'kiyani' => 'کیانی',
        'ayub kiyani' => ' ایوب کیانی',
        'kaleem' => 'کلیم',
        'saeem' => 'سائم',
        'hassan ali' => 'حسن علی',
        'tayab' => 'طیب',
        'tayab' => ' طیب',
        'azam shahzad bhatti' => 'اعظم شہزاد بھٹی',
        'shahzad mughal' => ' شہزاد مغل ',
        'riysat mehmood' => 'ریاست محمود',
        'awais' => 'اویس',
        'awais tayab' => 'اویس طیب',
        'sultan ali' => 'سلطان علی',
        'raja qaiser' => 'راجہ قیصر',
        'idress' => 'ادریس',
        'fuji' => 'فوجی',
        'irfan fuji' => 'عرفان فوجی',
        'shoaib' => 'شعیب',
        'nagham' => ' ناعم',
        'ishaq' => 'اسحاق',
        'khurshid' => ' خورشید',
        'fezaal' => ' فیضال',
        'riaz ul rehman' => ' ریاض الرحمن',
        'ammad khan' => 'عماد خان',
        'taj' => 'تاج',
        'bangash' => 'بنگش',
        'taj' => ' تاج',
        'm nuoman' => 'محمد نعمان',
        'nuoman ahmed' => ' نعمان احمد',
        'ameer' => 'امیر',
        'khatak' => 'خٹک',
        'ameer khatak' => 'امیر خٹک',
        'nasir' => 'ناصر',
        'abdul samad' => 'عبدالصمد',
        'samad khan' => 'صمد حان',
        'yaqoob' => 'یعقوب',
        'nasir yaqoob' => 'ناصر یعقوب',
        'm usaman' => 'محمد عثمان',
        'usaman' => ' عثمان',
        'zaib' => ' زیب',
        'm khan' => 'محمد خان',
        'abdullah' => 'عبداللہ',
        'saqi' => 'ساقی',
        'hamed' => 'حامد',
        'abdul hamed' => 'عبدالحامد',
        'taish' => 'تائش',
        'zulkifal' => 'ذوالکفل',
        'saqi khan' => 'ساقی خان',
        'hamid khan' => 'حمید خان',
        'unknown customer' => 'نامعلوم گاہک',
        'no address' => 'بغیر پتہ',
        'ihtisham' => 'احتشام',
        'malak hassan shehzad' => 'ملک حسن شہزاد',
        'khuram' => 'خرم',
        'junaid shehzad' => 'جنید شہزاد',
        'zafer' => 'ظافر',
        'hamza zafer' => 'حمزہ ظفر',
        'musa' => 'موسیٰ',
        'm musa' => 'محمد موسیٰ',
        'ashir' => 'عاشِر',
        'iftikhar' => 'افتخار',
        'abdur razaq' => 'عبدالرزاق',
        'karnal dawood' => 'کرنل داود',
        'dawood' => ' داود',
        'adnan' => 'عدنان',
        'm adnan gull' => 'محمد عدنان گل',
        'hamad' => 'حماد',
        'azhar' => 'اظہر',
        'mazher' => 'مظہر',
        'faraz' => 'فراز',
        'mukheet' => 'محیط',
        'sajeel' => 'سجیل',
        'abdul latif' => 'عبدالطیف',
        'latif' => 'لطیف',
        'razaq' => 'رزاق',
        'abdul ghani' => 'عبدالغنی',
        'mahmmood' => 'محمود',
        'azhar mahmmood' => 'اظہر محمود',
        'farooq' => 'فاروق',
        'khasta' => 'حستہ',
        'huidat' => ' خویدات',
        'noor ul din' => ' نور الدین',
        'mujtabha' => 'مجتبیٰ',
        'syed hassan mujtabha' => 'سید حسن مجتبیٰ',
        'umair' => 'عمیر',
        'taroz' => 'تروز',
        'babar' => 'بابر',
        'wasif' => 'واصف',
        'khiqman u din' => 'حکمان الدین',
        'khalid' => 'خالد',
        'umair khalid' => 'عمیر خالد',
        'bangash' => 'بنگش',
        'asif bangash' => 'آصف بنگش',
        'raja zahid' => 'راجہ زاہد',
        'rizwan khan' => 'رضوان خان',
        ' hussain' => ' حسین',
        'qaiser' => 'قیصر',
        'safwan' => 'صفوان',
        'awais khan' => 'اویس خان',
        'qasim ali' => 'قاسم علی',
        'awais' => ' اویس',
        'khandam' => 'خندم',
        'khandam khan' => 'خندم خان',
        'amad' => 'عماد',
        'hashim' => 'ہاشم',
        'bader' => 'بدر',
        'sarim' => 'صارم',
        'furniture' => 'فرنیچر',
        'amad furniture' => 'عماد فرنیچر',
        'malak amjid' => 'ملک امجد',
        'm khashir ali khan' => 'محمد خاشر علی خان',
        'malik bari' => 'ملک باری',
        'hitab' => 'ہتاب',
        'kalimullah' => 'کلیم اللہ',
        'kalim a' => 'کلیم ',
        'rabiull hassan' => 'ربیع الحسن ',
        'umar hitab' => 'عمر ہتاب',
        'waheed akhtar' => 'وحید اختر',
        'akhtar' => ' اختر',
        'siyam' => 'سیام',
        'mohsan' => 'محسن',
        'siyam bashir' => 'سیام بشیر',
        'arqam' => 'ارقم',
        'sheraz' => 'شیراز',
        'sheri' => 'شیری',
        'waleed' => 'ولید',
        'hamees' => 'حمیس',
        'arqam bhatti' => 'ارقم بھٹی',
        'rafy' => 'رفیع',
        'abdur rafy' => 'عبدالرفیع',
        'nazeer' => 'نذیر',
        'safdar' => 'صفدر',
        'nazeer hussain' => 'نذیر حسین',
        'zain ul rehman' => 'زین الرحمان',
        'zeeshan waheed' => 'ذیشان وحید',
        'hafiz' => 'حافظ',
        'awan' => 'اعوان',
        'rehan' => 'ریحان',
        'sarvar' => 'سرور',
        'tufail' => 'طفیل',
        'abdul mateen' => 'عبدالمتین',
        'shahid' => 'شاہد',
        'abdul manan' => 'عبدالمنان',
        'abdul majeed' => 'عبدالمجید',
        'm hafiz awan' => 'محمد حافظ اعوان',
        'saim' => 'صائم',
        'shahrukh' => 'شاہ رخ',
        'aksir' => 'اکثر ',
        'tabish' => 'تابش ',
        'bin' => 'بن',
        'saim bin sajid' => 'سائم بن ساجد',
        'shahzeb' => 'شہزیب',
        'm shahzeb' => 'محمد شہزیب',
        'abid' => 'عابد',
        'ruban' => 'ربان',
        'robin' => 'روبن',
        'damial' => 'دامیال',
        'abid damial' => 'عابد دامیال',
        'aqeel' => 'عقیل',
        'sharjeel' => ' شرجیل',
        'nafees' => ' نفیس',
        'ayub' => 'ایوب',
        'manzoor' => 'منظور',
        'manzer' => 'منظر',
        'm ayub' => 'محمد ایوب',
        'khalil ul rehman' => 'خلیل الرحمان',
        'zubair hussain' => 'زبیر حسین',
        'khalil haider' => 'خلیل حیدر',
        'qaiser mahmmod' => 'قیصر محمود',
        'faizan haider' => 'فیضان حیدر',
        'hamayat' => 'ہمایت',
        'hamayat ali' => 'ہمایت علی',
        'mahmmod' => 'محمود',
        'aftab' => 'افتاب',
        'arsalan' => 'ارسلان',
        'samar' => 'ثمر',
        'azhar iqbal' => 'اظہر اقبال',
        'ibrahim' => 'ابراہیم',
        'ibrahim salman' => 'ابراہیم سلمان',
        'suban' => 'ثوبان',
        'suban salman' => 'ثوبان سلمان',
        'irfan khan' => 'عرفان خان',
        'asif kiyani' => 'آصف کیانی',
        'hasaad' => 'حساد',
        'saad' => 'سعد',
        'rafaqit' => 'رفاقت',
        'soheeb' => 'صحیب',
        'hafeez' => 'حفیظ',
        'fuzan' => 'فوزان',
        'abdul hafeez' => 'عبدالحفیظ',
        'irfanullah' => 'عرفان اللہ',
        'abdul hasaan' => 'عبدالحسان',
        'iskhaq' => 'اسحاق',
        'sabeel' => 'سبیل',
        'shafiq' => 'شفیق',
        'shafiq' => ' شفیق',
        'jasim' => ' جاسم',
        'raja zain' => 'راجہ زین',
        'azam shahzad bhatti' => 'اعظم شہزاد بھٹی',
        'qadoos' => 'قدوس',
        'abdul qadoos' => 'عبدالقدوس',
        'sajid khan' => 'ساجد خان',
        'umair' => 'عمیر',
        'asim shah' => 'عاصم شاہ',
        'sana ullah' => 'ثناء اللہ',
        'rahim ullah' => 'رحیم اللہ',
        'wasif ullah' => 'وسیف اللہ',
        'ali abbass' => 'علی عباس',
        'shoaib syed hamid ali' => 'شعیب سید حمید علی',
        'wasi' => 'وصی',
        'abdul rahim' => 'عبدالرحیم',
        'qari abdul rahim' => 'قاری عبدالرحیم',
        'vaiz' => 'ویز',
        'wasi abbas' => 'وصی عباس',
        'sameer' => 'ثمر',
        'fakhar' => 'فخر',
        'naseeb' => 'نصیب',
        'riyaz' => 'ریاض',
        'abdul ahad' => 'عبدالاحد',
        'wali' => 'ولی',
        'asghar' => 'اصغر',
        'ansaar' => 'انصار',
        'masroof' => 'مصروف',
        'masood' => 'مسعود',
        'inam' => 'انعام',
        'inam ul haq' => 'العام الحق',
        'salam' => 'سلام',
        'shereen' => 'شرین',
        'zuman' => 'زمان',
        'zumair' => 'زمائر',
        'ijaz ul haq' => 'اعجازالحق',
        'lebnan' => 'لبنان',
        'faheem' => 'فہیم',
        'naseem' => 'نسیم',
        'abdul saboor' => 'عبدالصبور',
        'fahad' => 'فہد ',
        'mafsar' => 'مفسر ',
        'arham' => 'ارحم ',
        'tuseef' => 'توصیف ',
        'naseer' => 'نصیر ',
        'usman asgher' => 'عثمان اصغر ',
        'zuraib' => 'ذوریب ',
        'ihtisham akhtar' => 'احتشام اختر ',
        'anjum' => 'انجم ',
        'hafiz abdul wajid' => 'حافط عبدالواجد ',
        'abdul wajid' => ' عبدالواجد ',
        'abdul sitar' => ' عبدالستار ',
        'atah' => 'عطا ',
        'ihtisham ul haq' => 'احتشام الحق ',
        'uzair qadir' => ' اوزیر قدیر ',
        'uzair' => ' اوزیر  ',
        'qazim' => ' کاظم  ',
        'fareed' => ' فرید  ',
        'gullzar' => ' گلزار  ',
        'zarwali' => ' زروالی  ',
        'wajdan' => ' وجدان  ',
        'alvi' => ' علوی  ',
        'qadir shameer' => '  قدیر شمیر  ',
        'qadir' => '  قدیر ',
        'qader' => '  قادر ',
        'ul rehman' => 'الرحمن ',
        'atah ul rehman' => 'عطا الرحمن ',
        'artaza abbassi' => 'ارتضیٰ عباسی ',
        'artaza' => 'ارتضیٰ  ',
        'daiym' => 'دائم  ',
        'abbassi' => ' عباسی ',
        'shamshad' => 'سمشاد ',
        'abdul rauof' => 'عبدالروف ',
        'rauof' => 'روٴف ',
        'badal' => 'بادل ',
        'salak' => 'صالک ',
        'sameer naseem' => 'سمیر نسیم',
        'tahir abbas' => 'طاہر عباس',
        'sarfaraz' => 'سرفراز',
        'syed wajahat ali' => 'سید وجاہت علی',
        'atta' => 'عطا ',
        'azghar' => 'اظہر ',
        'muheb' => 'محب ',
        'abdul salam' => 'عبدالسلام ',
        'mudasar khan' => 'مدثر خان',
        'sajad ahmed' => 'سجاد احمد',
        'ali hasan' => 'علی حسن',
        'asad ali' => 'اسد علی',
        'haseeb girja' => 'حسیب گرجا',
        'roshan' => 'روشن',
        'roshan arshad' => 'روشن ارشد',
        'zulfiqar' => 'ذوالفقار',
        'shair' => 'شیر',
        'shair ali' => 'شیر علی',
        'abu al hassan' => 'ابوالحسن',
        'saheeb' => 'صحیب',
        'zaryan' => 'زریان',
        'zakariya' => 'زکریا',
        'iltaf' => 'الطاف',
        'saheeb iltaf' => 'صحیب الطاف',
        'shahir' => 'شاہر',
        'zia' => 'ضیاء',
        'bilal iftikhar' => 'بلال افتخار',
        'chaudhry' => 'چودھری',
        'shaban' => 'شعبان',
        'mehran' => 'مہران',
        'maher' => 'مہر',
        'shaukat' => ' شوکت',
        'saleh' => ' صالح',
        'sohaib' => ' صہیب',
        'alam' => ' عالم',
        'sajwal' => ' سجاول',
        'aaliyan' => ' عالیان',
        'ghulam mehfooz' => ' غلام محفوظ',
        'ahsan ul khaq' => ' احسان الحق',
        'zulqarnain' => ' ذوالقرنین',
        'zafer' => ' ظفر',
        'viky' => ' وکی',
         'ammar' => ' عمار',
         'rahail' => ' راحیل',
         'abu hurara' => ' ابوھریرہ',
         'zaki ul hassan' => ' زکی الحسن',
         'mehtab' => ' مہتاب',
         'abdul hadi' => ' عبدالہادی',
         'aqib' => ' عاقب',
         'wasiq' => ' واسق',
         'chanvaiz' => ' چنویز',
         'hamas' => ' حماس',
         'safi' => ' صفی',
         'zaryaf' => ' زریاف',
         'mumdi' => ' ممدی',
         'khitab' => ' خطاب',
         'kawala' => ' کویالہ',
         'shiekh' => ' شیخ',
         'sahil' => ' ساحل',
         'haleem' => ' حلیم',
         'aaiz' => ' عائز',
         'tehzeem' => ' تہز یم',
         'abdul hamid' => ' عبدالحمید',
         'abdul jabar' => ' عبدالجبار',
         'shoukat' => ' شوکت',
         'qabeer' => ' کبیر',
         'qabeer' => ' کبیر1',
         'arif khan' => ' عارف خان',
         'arif' => ' عارف ',
         'abdul raheed' => ' عبدالرشید',
         'hadeed' => ' حدید',
         'azad' => ' آزاد',
         'nazar' => ' نظر',
         'abd' => ' عبد',
         'ubaid' => ' عبید',
         'khizar' => ' 	خضر',
         'abdul hakeem' => ' عبدالحکیم',
          'rana ubaid' => ' رانا عبید',
         'samriaz' => ' ثمریز',
         'saeed' => ' سعید',
         'kukar' => ' کھوکھر',
         'shafay' => ' شافع',
         'rajab' => ' رجب',
         'daraz' => ' دراز',
         'shafqit' => ' شفقت',
         'mugheed' => ' عبدالمعید',
         'jabran' => ' جبران',
         'munsib' => ' منصب',
         'abdul munhem' => ' عبدالمنحیم',
         'insar' => ' انصار',
         'haq' => ' حق',
         'mughez' => ' عبدالمعیز',
         'abdul rouf' => ' عبدالروف',
         'abdul aziz' => ' عبدالروف',
         'abdul rashid' => ' عبدالرشید',
         'qurashi' => ' قریشی',
         'zarham' => ' زرغام',
         'tehseen' => ' تحسین',
         'sharim' => ' شارم',
         'rashid' => ' راشد',
         'mebraiz' => ' ممبریز',
         'farukh saeed' => 'فرخ سعید',
         'sohaib hassan' => ' صہیب حسن',
         'muneer ahmed' => ' منیراحمد',
         'muneer' => ' منیر',
         'Roshaan' => ' روشان',
         'hurara' => ' ھریرہ',
         'ruhan' => ' روحان',
         'riyan' => ' ریان',
         'saher' => ' ساحر',
         'azlan' => ' اضلان',
         'azaz' => ' اعزاز',
         'shahzaib' => ' شاہ زیب',
         'sobedar' => '  صوبیدار',
         'akmal' => '  اکمل',
         'pumi' => '  پومی',
         'raja ajab' => ' راجہ عجب',
         'saghadit' => ' سعادت',
         'sagheer' => ' صغیر',
         'ajmal' => ' اجمل',
         'saeed ur rehman' => ' سیدالرحمن',
         'naeem mughal' => ' نعیم مغل',
         'mughal' => '  مغل',
         'ansar' => '  انصر',
         'sadeem' => '  صدیم',
         'saram' => '  صارم',
         'khawar' => '  خاور',
         'saghir' => '  ساغر',
         'sheroz' => '  شیروز',
         'tuoseef' => '  توصیف',
         'zulqarnain' => '  	ذوالقرنین',
         'sakhawit' => ' سخاوت',
         'aban' => ' عبان',
         'malak hameed' => ' ملک حمید',
         'abaid rehman' => ' عبید الرحمان',
         'sajid jamail' => ' ساجد جمیل',
         'ismail' => ' اسما عیل',
         'haseeb sajid' => '  حسیب ساجد',
        'chaudhry shaban' => 'چودھری شعبان',
        
        // مخففات
        'm' => 'M',
        'a' => 'A',
        's' => 'سید',
        'g' => '',
        'f' => '',
        'e' => '',
        'd' => '',
        'c' => '',
        'b' => '',
    ];
    
    $input = strtolower(trim($englishName));
    
    // پہلے مکمل نام ڈکشنری میں تلاش کریں
    foreach ($namesDictionary as $eng => $urd) {
        if ($eng !== '' && strpos($input, $eng) !== false) {
            // اگر مکمل مل گیا تو واپس کریں
            if ($input === $eng) {
                return $urd;
            }
        }
    }
    
    // الفاظ کو الگ الگ تبدیل کریں
    $words = preg_split('/\s+/', $input);
    $urduWords = [];
    
    foreach ($words as $word) {
        $word = trim($word);
        if ($word === '') continue;
        
        if (isset($namesDictionary[$word])) {
            $urduWords[] = $namesDictionary[$word];
        } else {
            $urduWords[] = basicTransliteration($word);
        }
    }
    
    return implode(' ', $urduWords);
}

function basicTransliteration($word) {
    $simpleMap = [
        'a' => 'ا', 'b' => 'ب', 'c' => 'س', 'd' => 'د', 'e' => 'ے',
        'f' => 'ف', 'g' => 'گ', 'h' => 'ہ', 'i' => 'ی', 'j' => 'ج',
        'k' => 'ک', 'l' => 'ل', 'm' => 'م', 'n' => 'ن', 'o' => 'و',
        'p' => 'پ', 'q' => 'ق', 'r' => 'ر', 's' => 'س', 't' => 'ت',
        'u' => 'ء', 'v' => 'و', 'w' => 'و', 'x' => 'کس', 'y' => 'ی',
        'z' => 'ز',
        
        'sh' => 'ش', 'ch' => 'چ', 'kh' => 'خ', 'gh' => 'غ',
        'th' => 'تھ', 'dh' => 'دھ', 'ph' => 'پھ', 'bh' => 'بھ',
        'aa' => 'آ', 'ee' => 'ی', 'oo' => 'و', 'ai' => 'ئی',
        'ei' => 'ئی', 'ou' => 'ؤ',
    ];
    
    $result = $word;
    
    foreach (['sh', 'ch', 'kh', 'gh', 'th', 'dh', 'ph', 'bh', 'aa', 'ee', 'oo', 'ai', 'ei', 'ou'] as $combo) {
        if (isset($simpleMap[$combo])) {
            $result = str_replace($combo, $simpleMap[$combo], $result);
        }
    }
    
    $final = '';
    for ($i = 0; $i < strlen($result); $i++) {
        $char = $result[$i];
        if (isset($simpleMap[$char])) {
            $final .= $simpleMap[$char];
        } else {
            $final .= $char;
        }
    }
    
    return $final;
}
?>

<style>
.urdu-name {
    font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', 'Urdu Typesetting', Tahoma, sans-serif;
    font-size: 18px;
    font-weight: bold;
    color:#000;
    direction: rtl;
    text-align: right;
    line-height: 1.6;
    display: inline-block;
}
</style>

<?php 
if(isset($customer) && isset($customer['fullname'])) {
    $englishName = truncateText($customer['fullname'], 25); // پورے ناموں کے لیے length بڑھائی
    $urduName = englishToUrduName($englishName);
    
    echo '<span class="urdu-name">';
    echo htmlspecialchars($urduName);
    echo '</span>';
} else {
    echo '<span class="urdu-name">دست‌یاب نہیں</span>';
}
?>

<?php 
if(isset($customer) && isset($customer['fullname'])) {
    $englishName = truncateText($customer['fullname'], 10);
    $urduName = englishToUrduName($englishName);
    
    echo '<span style="font-family: \'Noto Nastaliq Urdu\', \'Jameel Noori Nastaleeq\', Tahoma, sans-serif;color:#000; font-size: 18px; font-weight: bold; direction: rtl;">';
   
    echo '</span>';
} else {
    echo '<span style="font-family: \'Noto Nastaliq Urdu\', \'Jameel Noori Nastaleeq\', Tahoma, sans-serif;color:#000; font-size: 18px; font-weight: bold; direction: rtl;">دست‌یاب نہیں</span>';
}
?>
</span>
     <p style="margin-bottom: 0;">We Propose New Style Every Day</p>

 <span>Size No: <?php echo htmlspecialchars($customer['address']); ?></span>

</div>
    <p style="margin-bottom: 0;">Jalal Arcade, Mohra Chappar Stop Chakri Road Rawalpindi</p>
    <p style="margin-bottom: 0;">Abdul Jabbar: 0313-5086286</p>
   </div>
    <?php

    $id = $_GET["id"];

    // Fetch order using prepared statement
    $stmt = $pdo->prepare("SELECT * FROM `order` WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch customer using prepared statement
    $stmt = $pdo->prepare("SELECT fullname, phonenumber, address FROM customer WHERE id = ?");
    $stmt->execute([$order['customer']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch staff using prepared statement
    $stmt = $pdo->prepare("SELECT fullname FROM staff WHERE id = ?");
    $stmt->execute([$order['received_by']]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>

    <div class="row">
      <div class="col-md-12">
        <table class="table table-bordered">
          <thead>
            <tr>
              <!-- <th>Name</th>
              <th>Size No</th> -->
              <!-- <th>Phone</th> -->
              <!-- <th>Date</th>
              <th>Total</th>
              <th>Paid</th>
              <th>Balance</th> -->
            </tr>
          </thead>
          <tbody>
          <tr>
              <td><div style="display: flex; justify-content: space-between; align-items: center;">
    <span>Collected Date: <?php echo htmlspecialchars($order['date_collected']); ?></span>
    <span>Amount: Rs. <?php echo number_format(($order['amount']), 2); ?></span>
    <span>Paid: Rs. <?php echo number_format(($order['paid']), 2); ?></span>
    <span>Balance: Rs. <?php echo number_format(($order['balance']), 2); ?></span>
</div></td>
              <!-- <td> <div><?php echo htmlspecialchars($customer['address']); ?></div></td> -->
            <!-- <td><div><?php echo htmlspecialchars($customer['phonenumber']); ?></div></td> -->
              <!-- <td><div><?php echo htmlspecialchars($order['date_collected']); ?></div></td>
              <td> <div>Rs. <?php echo htmlspecialchars($order['amount']); ?></div></td>
              <td><div>Rs. <?php echo number_format(($order['paid']), 2); ?></div></td>
              <td> <div>Rs. <?php echo htmlspecialchars($order['balance']); ?></div></td> -->
              
            </tr>
          </tbody>
        </table>
        <div class="main-meas" style="display: flex;">
          <div class="custom-receipt-measurement">
          </div>

       
         <div style="width: 71%;">
  <div class="suit-design-parent">
    <div class="suit-design">


<div style="display: flex; flex-direction: column; gap: 4px;">
  
  <!-- Row 1: Pockets -->
  <div style="display: flex; flex-direction: row; gap: 6px; align-items: flex-start; justify-content: flex-end;">
    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
          <div class="div" data-id="pockets-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
          <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>شلوار
        </div>
      </div>
    </div>

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
       <div class="div" data-id="pockets-3" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
          <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>2 سائیڈ
        </div>
      </div>
    </div>

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
        <div class="div" data-id="pockets-4" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
          <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>سائیڈ 1
        </div>
      </div>
    </div>

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
         <div class="div" data-id="pockets-5" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
          <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>فرنٹ
        </div>
      </div>
    </div>

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
        <div class="div" data-id="pockets-6" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
          <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>4 پاکٹ
        </div>
      </div>
    </div>
  </div>
  <!-- End Pockets Row -->

  <!-- Row 2: Collar Type -->
  <div style="display: flex; flex-direction: row; gap: 6px; align-items: flex-start; justify-content: flex-end;">
    <div class="div" data-id="collar-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
        <div class="div" data-id="collar-7" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
          <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>فرنچ
        </div>  
      </div>
    </div>

    <div class="div" data-id="collar-4" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
        <div class="div" data-id="collar-4" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
          <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>چھوٹا
        </div>
      </div>
    </div>

    <div class="div" data-id="collar-5" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
        <div class="div" data-id="collar-5" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
          <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>نارمل
        </div>
      </div>
    </div>

    <div class="div" data-id="collar-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
        <div class="div" data-id="collar-6" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
          <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>کالر
        </div>
      </div>
    </div>
  </div>
  <!-- End Collar Type Row -->

  <!-- Row 3: Collar Style -->
  <div style="display: flex; flex-direction: row; gap: 6px; align-items: flex-start; justify-content: flex-end;">

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
        <div class="div" data-id="collar-8" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
          <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>گول 
        </div>
      </div>
    </div>

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
        <div class="div" data-id="collar-9" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
          <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>چورس 
        </div>
      </div>
    </div>

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
        <div class="div" data-id="collar-3" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
          <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>فل بین
        </div>
      </div>
    </div>

    <div class="div" data-id="collar-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
        <div class="div" data-id="collar-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
          <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>کٹ بین
        </div>
      </div>
    </div>
  </div>
  <!-- End Collar Style Row -->


  <div style="display: flex; flex-direction: row; gap: 6px; align-items: flex-start; justify-content: flex-end;">
    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
        <div class="div" data-id="collar-10" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
          <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>کٹ 
        </div>
      </div>
    </div> 

    <div class="div" data-id="kuff-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
    <div class="div" data-id="kuff-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>گول بازو
    </div>
    </div>
    </div>

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
    <div class="div" data-id="kuff-3" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>سٹڈ
    </div>
      </div>
    </div>

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
    <div class="div" data-id="kuff-4" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>گول
      کف
    </div>
      </div>
    </div>

    <div class="div" data-id="collar-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
    <div class="div" data-id="kuff-5" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>کف چورس
    </div>  
      </div>
    </div>
  </div>
  <!-- End Kuff Style Row -->

  <div style="display: flex; flex-direction: row; gap: 6px; align-items: flex-start; justify-content: flex-end;">
    <div class="div" data-id="button-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
    <div class="div" data-id="button-4" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>سادہ
    </div> 
      </div>
    </div>

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
     <div class="div" data-id="button-3" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>رنگ
    </div>
    </div>
    </div>

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
    <div class="column" style="display: block;">
    <div class="div" data-id="button-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>میٹل
    </div>
      </div>
    </div>

    <div class="div" data-id="collar-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
    <div class="div" data-id="button-5" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>فینسی بٹن
    </div>
      </div>
    </div>
  </div>
  <!-- End Button - Separate columns in one row -->

<div style="display: flex; flex-direction: row; gap: 6px; align-items: flex-start; justify-content: flex-end;">
    <div class="div" data-id="ghera-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
      </div>
    </div>

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
     <div class="div" data-id="ghera-4" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>گھیرا ڈبل
    </div>
    </div>
    </div>

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
    <div class="div" data-id="ghera-3" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>گھیرا گول
    </div>
      </div>
    </div>

    <div class="div" data-id="collar-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
     <div class="div" data-id="ghera-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>چورس دامن
    </div>
      </div>
    </div>
  </div>
  <!-- End Ghera - 3 items in one row -->

<div style="display: flex; flex-direction: row; gap: 6px; align-items: flex-start; justify-content: flex-end;">
    <div class="div" data-id="patti-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
         <div class="div" data-id="patti-5" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span> ڈوری گلا
    </div>
      </div>
    </div>

    <div class="div" data-id="patti-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
         <div class="div" data-id="patti-6" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span> گم پٹی
    </div>
      </div>
    </div>

    <div class="div" data-id="patti-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
         <div class="div" data-id="patti-4" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>پٹی سوا
    </div>
      </div>
    </div>

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
     <div class="div" data-id="patti-3" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>پٹی پونا
    </div>
    </div>
    </div>

    <div class="div" data-id="pockets-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
   <div class="div" data-id="patti-2b" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>پٹی ایک انچ
    </div>
      </div>
    </div>
  </div>
  <!-- End Patti - 4 items in one row -->


<div style="display: flex; flex-direction: row; gap: 6px; align-items: flex-start; justify-content: flex-end;">
    <div class="div" data-id="chamak-3" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
     <div class="div" data-id="chamak-4" style="font-family: inherit; direction: rtl; text-align: center;    width: 104px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>چمک سنگل
    </div>
    </div>
    </div>

    <div class="div" data-id="chamak-3" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
   <div class="div" data-id="chamak-2" style="font-family: inherit; direction: rtl; text-align: center;width: 100px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>موٹا چمک
    </div>
      </div>
    </div>

    <div class="div" data-id="chamak-3" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
    <div class="div" data-id="chamak-3" style="font-family: inherit; direction: rtl; text-align: center;width: 110px;padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>فل ڈبل چمک
    </div>
      </div>
    </div>
  </div>
  <!-- End Chamak - 5 items in one row -->


<div style="display: flex; flex-direction: row; gap: 6px; align-items: flex-start; justify-content: flex-end;">
    <div class="div" data-id="chamak-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
       <div class="div" data-id="pant-4" style="font-family: inherit; direction: rtl; text-align: center;width: 85px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="font-family: Arial, sans-serif;">✔️</span>پانچہ جالی
    </div>
    </div>
    </div>

    <div class="div" data-id="chamak-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
       <div class="div" data-id="pant-5" style="font-family: inherit; direction: rtl; text-align: center;width: 97px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="font-family: Arial, sans-serif;">✔️</span>پانچہ کڑھائی
    </div>
    </div>
    </div>

    <div class="div" data-id="chamak-3" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
   <div class="div" data-id="chamak-6" style="font-family: inherit;width: 108px; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>فرنٹ ڈبل
    </div>
      </div>
    </div>

    <div class="div" data-id="chamak-3" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
   <div class="div" data-id="chamak-5" style="font-family: inherit; direction: rtl; text-align: center;width: 80px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>فل ڈبل
    </div>
      </div>
    </div>
  </div>
  <!-- End Chamak - 5 items in one row -->


<div style="display: flex; flex-direction: row; gap: 6px; align-items: flex-start; justify-content: flex-end;">
    <div class="div10" data-id="chamak-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
    <div class="div" data-id="pant-3" style="font-family: inherit; direction: rtl; text-align: center;width: 92px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="font-family: Arial, sans-serif;">✔️</span> ٹراؤزر
    </div>
    </div>
    </div>

    <div class="div" data-id="chamak-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
       <div class="div" data-id="pant-1" style="font-family: inherit; direction: rtl; text-align: center;width: 92px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="font-family: Arial, sans-serif;">✔️</span>فل ٹراؤزر
    </div>
    </div>
    </div>

    <div class="div" data-id="chamak-3" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
           <div class="div" data-id="pant-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="font-family: Arial, sans-serif;">✔️</span> ٹراؤزر شیپ
    </div>
      </div>
    </div>

    <div class="div" data-id="chamak-3" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
   <div class="div" data-id="gaddi-1" style="font-family: inherit; direction: rtl; text-align: center;width:80px; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="font-family: Arial, sans-serif;">✔️</span>گڈی 
    </div>
      </div>
    </div>
  </div>
  <!-- End Shalwar - 5 items in one row -->


<div style="display: flex; flex-direction: row; gap: 6px; align-items: flex-start; justify-content: flex-end;">
    <div class="div" data-id="collar-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
     <div class="div" data-id="linekapra-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>لائِنِن٘گ کپڑا
    </div>
    </div>
    </div>
     <div class="div" data-id="collar-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
     <div class="div" data-id="patti-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>پٹی نوک
    </div>
    </div>
    </div>

    <div class="div" data-id="chamak-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
      <div class="div" data-id="design-1" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>ڈیزائن 
    </div>
    </div>
    </div>

    <div class="div" data-id="chamak-3" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
     <div class="div" data-id="design-2" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>SF بکرم
    </div>
      </div>
    </div>

    <div class="div" data-id="chamak-3" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
   <div class="div" data-id="design-3s" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>باریک سلائی
    </div>
      </div>
    </div>
  </div>
  <!-- End Additional Unique IDs -->


<div style="display: flex; flex-direction: row; gap: 6px; align-items: flex-start; justify-content: flex-end;">

  <div class="div" data-id="chamak-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
     <div class="div" data-id="design-3b" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span> بلوچی شلوار
    </div>
    </div>
    </div>
    <div class="div" data-id="chamak-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
     <div class="div" data-id="design-3a" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>5 بٹن
    </div>
    </div>
    </div>

    <div class="div" data-id="chamak-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
     <div class="div" data-id="design-3c" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>ڈبل بٹن
    </div>
    </div>
    </div>

    <div class="div" data-id="chamak-3" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
        <div class="div" data-id="edge-1" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>چاک پٹی کاج
    </div>
      </div>
    </div>

    <div class="div" data-id="chamak-3" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', serif; direction: rtl; font-size: 16px; padding:0px;margin:0px;border:0px; flex-shrink: 0;">
      <div class="column" style="display: block;">
     <div class="div" data-id="design-3d" style="font-family: inherit; direction: rtl; text-align: center; padding: 8px 3px; border: 1px solid #000000ff; border-radius: 4px;">
      <span class="check" style="display: none; font-family: Arial, sans-serif;">✔️</span>چاک پٹی لیبل
    </div>
      </div>
    </div>
  </div>
  <!-- End Design items - Row 1 -->
</div>
    </div>
  </div>
</div>

          <div style="width: 40%; border-left: 1px solid #e6e6e6; margin-left: 10px;">
              <?php

                $id = $_GET["id"];

                // Fetch measurements using prepared statement
                $stmt = $pdo->prepare("SELECT measurement.*, part.title, type.title as type_name FROM measurement
                          JOIN part ON measurement.part_id = part.id
                          JOIN type ON part.type = type.id
                      WHERE customer_id = ? AND measurement.measurement != ''");
                $stmt->execute([$order['customer']]);
                $measurements_rows = $stmt;
                
                $measurements = [];

                while ($row = $measurements_rows->fetch(PDO::FETCH_ASSOC)) {
                  $measurements[$row['type_name']][] = $row;
                }

                ?>
            <!-- <h1 style="text-align:center; font-weight: 700;"><?= isset($measurements[0]) ? $measurements[0]['type_name'] : '' ?></h1> -->

            <div class="row">
              <div class="col-md-12">

                <?php foreach ($measurements as $type => $mrs) : ?>

                  <div class="meas-box" style="border-top: 1px solid #e6e6e6; border-bottom: 1px solid #e6e6e6; padding-bottom: 10px;">

                    <!-- <a style="text-align:center; display: table; margin: 0 auto;" class="btn btn-primary btn-xs printbtn" href="javascript:;" onClick="mPrint('<?= strtolower($type) ?>')"><i class="icon-print icon-large"></i> Print Mesurement</a> -->
                    <div id="<?= strtolower($type) ?>">
                      <!-- <h5 style="text-align:center; font-weight: 700;"><?php echo htmlspecialchars($customer['fullname']); ?></h5> -->
                      <!-- <h5 style="text-align:center; font-weight: 700;"><?php echo htmlspecialchars($customer['address']); ?></h5> -->
                      <!-- <h4 style="text-align:center; font-weight: 700; color: #000"><?= htmlspecialchars($type) ?></h4> -->
                      <?php foreach ($mrs as  $m) { ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 10px;border-top: 1px solid #e6e6e6;  padding-top:10px;padding: 10px 15px 0 15px;">
                          <div style="font-size: 16px; font-weight: bold;color: #000"><b><?= htmlspecialchars($m['title']) ?></b></div>
                          <div style="font-size: 22px;; font-weight: bold;color: #000"><?= htmlspecialchars($m['measurement']) ?></div>
                        </div>

                      <?php } ?>
                    </div>
                  </div>
                <?php endforeach; ?>




              </div>


              <div class="col-md-6"></div>

            </div>


          </div>
        </div>

        <div class="seperator-cut" style="border-bottom: 2px dashed #000; width: 100%; margin: 20px 0; display: block;"></div>
        <div style="margin-bottom: 20px;">
        <h1 style="font-weight: 700; margin-top: 0; font-size: 24px; color: #000; text-align: center;">Bhatti Tailors & Fabrics</h1>
          <h3 style="font-weight: 700; margin-top: 0; font-size: 16px;; text-align: center;">Customer Receipt</h3>
        </div>
        <table class="table table-bordered">
        <thead>
          <tr>
            <th>Name</th>
            <th>Size No</th>
            <th>Phone</th>
            <th>Date</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Balance</th>
          </tr>
        </thead>
        <tbody>
        <tr>
            <td><div><?php echo htmlspecialchars($customer['fullname']); ?></div></td>
            <td> <div><?php echo htmlspecialchars($customer['address']); ?></div></td>
            <td><div><?php echo htmlspecialchars($customer['phonenumber']); ?></div></td>
            <td><div><?php echo htmlspecialchars($order['date_collected']); ?></div></td>
            <td> <div>Rs. <?php echo htmlspecialchars($order['amount']); ?></div></td>
            <td><div>Rs. <?php echo number_format(($order['paid']), 2); ?></div></td>
            <td> <div>Rs. <?php echo htmlspecialchars($order['balance']); ?></div></td>
          </tr>
        </tbody>
      </table>
      </div>



    </div><!-- ROW-->



    <!-- <div class="table-responsive">
    
      <div style='margin-left:40%;'><b>Staff Sign</b></div><br /><br />
      <div style='margin-left:40%;'><b>Customer Sign</b></div>

    </div> -->


    <br /><br />



  </div>



  

  <!-- <div class="pull-right">
    <a href="" onClick="mPrint('m-print')" class="btn btn-info printbtn"><i class="icon-print icon-large"></i> Print Mesurement</a>
  </div> -->


</div><!-- contentpanel -->






</section>


<?php
include('footer.php');
?>

<script src="js/jquery.datatables.min.js"></script>
<script src="js/select2.min.js"></script>
<script>
function toggleCheck(event) {
    event.stopPropagation(); 
    var checkSpan = event.currentTarget.querySelector(".check");
    var checkboxId = event.currentTarget.getAttribute("data-id");
    var orderId = <?php echo json_encode($order['id']); ?>;
    
    // Check if this is the specific element that needs cross behavior
    if (checkboxId === "edge-1" || checkboxId === "design-3c" || checkboxId === "design-3d") {
        // For these three: cycle through tick -> cross -> none
        var currentState = checkSpan.getAttribute("data-state") || "none";
        var isChecked = 0;
        
        console.log('Toggling:', checkboxId, 'Current state:', currentState);
        
        if (currentState === "none") {
            // First click: show tick
            checkSpan.innerHTML = "✔️";
            checkSpan.style.display = "inline";
            checkSpan.setAttribute("data-state", "tick");
            checkSpan.style.color = "green";
            isChecked = 1; // 1 = tick
        } else if (currentState === "tick") {
            // Second click: show cross
            checkSpan.innerHTML = "❌";
            checkSpan.style.display = "inline";
            checkSpan.setAttribute("data-state", "cross");
            checkSpan.style.color = "red";
            isChecked = 2; // 2 = cross
        } else {
            // Third click: hide
            checkSpan.style.display = "none";
            checkSpan.setAttribute("data-state", "none");
            checkSpan.innerHTML = "✔️"; // Reset to tick for next time
            isChecked = 0; // 0 = none
        }
        
        // Save to database
        saveToDatabase(orderId, checkboxId, isChecked);
    } else {
        // For all other elements: normal tick behavior
        var currentState = checkSpan.getAttribute("data-state") || "none";
        var isChecked = (currentState === "tick") ? 0 : 1;
        
        if (currentState === "tick") {
            checkSpan.style.display = "none";
            checkSpan.setAttribute("data-state", "none");
            isChecked = 0;
        } else {
            checkSpan.innerHTML = "✔️";
            checkSpan.style.display = "inline";
            checkSpan.setAttribute("data-state", "tick");
            isChecked = 1;
        }
        
        // Save to database
        saveToDatabase(orderId, checkboxId, isChecked);
    }
}

function saveToDatabase(orderId, checkboxId, isChecked) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "save_checkbox_state.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status !== 'success') {
                        console.error('Failed to save checkbox state: ' + response.message);
                    } else {
                        console.log('Saved successfully:', checkboxId, '=', isChecked);
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
            } else {
                console.error('Request failed with status:', xhr.status);
            }
        }
    };
    
    xhr.send("order_id=" + orderId + 
             "&checkbox_id=" + encodeURIComponent(checkboxId) + 
             "&is_checked=" + isChecked);
}

function initializeCheckStates() {
    var orderId = <?php echo json_encode($order['id']); ?>;

    // Fetch all checkbox states from database
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "fetch_checkbox_states.php?order_id=" + orderId, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                console.log('Fetched states:', response);
                
                if (response.status === 'success') {
                    var checkedItems = response.data || {};
                    var stateTypes = response.state_types || {};
                    
                    // Get all checkbox divs
                    var checkboxDivs = document.querySelectorAll(".div, .div1, .div2, .div3, .div4, .div5, .div6, .div7, .div8, .div9, .div10, .div11, .div12, .div13, .div14, .div15, .div16, .div17, .div18, .div19, .div20, .div21, .div22, .div23, .div24, .div25, .div26, .div27, .div28, .div29, .div30, .div31, .div32, .div33, .div34, .div35");
                    
                    checkboxDivs.forEach(function(div) {
                        var id = div.getAttribute("data-id");
                        if (!id) return; // Skip if no data-id
                        
                        var checkSpan = div.querySelector(".check");
                        if (!checkSpan) return; // Skip if no check span
                        
                       if (id === "edge-1" || id === "design-3c" || id === "design-3d") {
                            // Handle edge-1 with 3 states
                            var state = stateTypes[id] || "none";
                            var isChecked = checkedItems[id] || 0;
                            
                            console.log('Edge-1:', {id: id, state: state, isChecked: isChecked});
                            
                            checkSpan.setAttribute("data-state", state);
                            
                            if (state === "tick") {
                                checkSpan.innerHTML = "✔️";
                                checkSpan.style.display = "inline";
                                checkSpan.style.color = "green";
                            } else if (state === "cross") {
                                checkSpan.innerHTML = "❌";
                                checkSpan.style.display = "inline";
                                checkSpan.style.color = "red";
                            } else {
                                checkSpan.style.display = "none";
                                checkSpan.innerHTML = "✔️"; // Reset for next time
                            }
                        } else {
                            // Normal checkboxes
                            var isChecked = checkedItems[id] || 0;
                            var state = stateTypes[id] || (isChecked ? "tick" : "none");
                            
                            checkSpan.setAttribute("data-state", state);
                            
                            if (state === "tick") {
                                checkSpan.innerHTML = "✔️";
                                checkSpan.style.display = "inline";
                            } else {
                                checkSpan.style.display = "none";
                            }
                        }
                    });
                    
                    console.log('Initialized', checkboxDivs.length, 'checkboxes');
                } else {
                    console.error('Failed to fetch checkbox states: ' + response.message);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                console.log('Raw response:', xhr.responseText);
            }
        }
    };
    xhr.send();
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function() {
    console.log('Initializing checkbox states...');
    initializeCheckStates();
    
    // Add event listeners to all checkbox divs
    var checkboxes = document.querySelectorAll(".div, .div1, .div2, .div3, .div4, .div5, .div6, .div7, .div8, .div9, .div10, .div11, .div12, .div13, .div14, .div15, .div16, .div17, .div18, .div19, .div20, .div21, .div22, .div23, .div24, .div25, .div26, .div27, .div28, .div29, .div30, .div31, .div32, .div33, .div34, .div35");
    console.log('Found', checkboxes.length, 'checkboxes');
    
    checkboxes.forEach(function(div) {
        div.addEventListener("click", toggleCheck);
    });
    
    // Debug: Log all checkboxes with their IDs
    checkboxes.forEach(function(div, index) {
        var id = div.getAttribute("data-id");
        console.log('Checkbox', index, 'id:', id, 'element:', div);
    });
});

// Clear all checkboxes for testing
function clearAllCheckboxes() {
    var orderId = <?php echo json_encode($order['id']); ?>;
    
    // Clear all checkboxes visually
    document.querySelectorAll(".div, .div1, .div2, .div3, .div4, .div5, .div6, .div7, .div8, .div9, .div10, .div11, .div12, .div13, .div14, .div15, .div16, .div17, .div18, .div19, .div20, .div21, .div22, .div23, .div24, .div25, .div26, .div27, .div28, .div29, .div30, .div31, .div32, .div33, .div34, .div35").forEach(function(div) {
        var id = div.getAttribute("data-id");
        var checkSpan = div.querySelector(".check");
        if (checkSpan) {
            checkSpan.style.display = "none";
            checkSpan.setAttribute("data-state", "none");
            checkSpan.innerHTML = "✔️";
            checkSpan.style.color = "";
            
            // Save to database
            saveToDatabase(orderId, id, 0);
        }
    });
    
    alert("All checkboxes cleared!");
}
</script>

<script>
  jQuery(document).ready(function() {

    "use strict";

    jQuery('#table1').dataTable();

    jQuery('#table2').dataTable({
      "sPaginationType": "full_numbers"
    });

    // Select2
    jQuery('select').select2({
      minimumResultsForSearch: -1
    });

    jQuery('select').removeClass('form-control');

    // Delete row in a table
    jQuery('.delete-row').click(function() {
      var c = confirm("Continue delete?");
      if (c)
        jQuery(this).closest('tr').fadeOut(function() {
          jQuery(this).remove();
        });

      return false;
    });

    // Show aciton upon row hover
    jQuery('.table-hidaction tbody tr').hover(function() {
      jQuery(this).find('.table-action-hide a').animate({
        opacity: 1
      });
    }, function() {
      jQuery(this).find('.table-action-hide a').animate({
        opacity: 0
      });
    });


  });
</script>
<script>
function handlePrintDisplay() {
    var originalDiv = document.querySelector('.div');
    var checkSpan = originalDiv.querySelector('.check');
    var justVerifiedDiv = document.querySelector('.fr-text');

    // Always show original div on print
    originalDiv.style.display = 'block';

    // Show "just Verified" only if checkmark is missing
    if (!checkSpan || checkSpan.textContent.trim() !== '✔️') {
        justVerifiedDiv.style.display = 'block';
    } else {
        justVerifiedDiv.style.display = 'none';
    }
}

// Before print
window.addEventListener('beforeprint', handlePrintDisplay);

// After print: hide justVerified again
window.addEventListener('afterprint', function() {
    document.querySelector('.fr-text').style.display = 'none';
});
</script>


</body>

</html>