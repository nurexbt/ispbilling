<?php
$page_title = 'Instructions & Policies';
include 'includes/header.php';
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap');

    .stylish-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        transition: transform 0.2s ease-in-out;
    }
    .stylish-card:hover {
        transform: translateY(-2px);
    }
    .icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .safe-net-box {
        background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
    }
    .btrc-box {
        background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
    }
    .policy-content h6 {
        color: #1e293b;
        font-weight: 700;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }
    .policy-content h6:first-child {
        margin-top: 0;
    }
    .policy-content p {
        color: #64748b;
        font-size: 0.95rem;
        line-height: 1.7;
        margin-bottom: 0.5rem;
    }
    .bangla-text {
        font-family: 'Hind Siliguri', sans-serif !important;
    }
    .english-text {
        font-family: 'Segoe UI', Arial, sans-serif !important;
    }
</style>

<div class="d-flex align-items-center justify-content-between mb-4 mt-2">
    <div>
        <h4 class="mb-1 text-slate-900 fw-bold bangla-text" style="letter-spacing: -0.5px;">নির্দেশনা এবং নীতিমালা</h4>
        <p class="text-slate-500 mb-0 bangla-text" style="font-size: 0.95rem;">গুরুত্বপূর্ণ নির্দেশিকা, নিরাপদ ইন্টারনেট ব্যবহার এবং আইনি নীতিমালা</p>
    </div>
</div>

<div>
<!-- Top Section: Safe Internet & BTRC -->
<div class="row g-4 mb-4">
    <!-- Safe Internet Use -->
    <div class="col-md-6">
        <div class="card stylish-card h-100 safe-net-box p-4" style="position: relative; overflow: hidden;">
            <div style="position: absolute; right: -20px; top: -20px; opacity: 0.1; font-size: 150px; pointer-events: none;">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="d-flex align-items-center mb-3">
                <div class="bg-white text-info icon-box shadow-sm me-3">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h5 class="mb-0 fw-bold text-white bangla-text">নিরাপদ ইন্টারনেট ব্যবহার</h5>
            </div>
            <ul class="text-white list-unstyled mb-0 bangla-text" style="font-size: 0.95rem; line-height: 1.8; opacity: 0.9;">
                <li><i class="bi bi-check-circle-fill me-2 text-white"></i> <strong>শক্তিশালী পাসওয়ার্ড:</strong> অননুমোদিত অ্যাক্সেস রোধ করতে সর্বদা একটি শক্তিশালী ওয়াইফাই পাসওয়ার্ড ব্যবহার করুন।</li>
                <li><i class="bi bi-check-circle-fill me-2 text-white"></i> <strong>সন্দেহজনক লিঙ্ক এড়িয়ে চলুন:</strong> আপনার ব্যক্তিগত ডেটা সুরক্ষিত রাখতে অজানা বা ক্ষতিকারক লিঙ্কে ক্লিক করবেন না।</li>
                <li><i class="bi bi-check-circle-fill me-2 text-white"></i> <strong>ডিভাইস আপডেট রাখুন:</strong> আপনার কম্পিউটার এবং মোবাইল ডিভাইসগুলো সর্বশেষ সিকিউরিটি প্যাচ দিয়ে আপডেট রাখুন।</li>
                <li><i class="bi bi-check-circle-fill me-2 text-white"></i> <strong>প্যারেন্টাল কন্ট্রোল:</strong> শিশুদের নিরাপদ ব্রাউজিং নিশ্চিত করতে তাদের ইন্টারনেট কার্যক্রম নজরে রাখুন।</li>
            </ul>
        </div>
    </div>
    
    <!-- BTRC Log Server -->
    <div class="col-md-6">
        <div class="card stylish-card h-100 btrc-box p-4" style="position: relative; overflow: hidden;">
            <div style="position: absolute; right: -20px; top: -20px; opacity: 0.1; font-size: 150px; pointer-events: none;">
                <i class="bi bi-server"></i>
            </div>
            <div class="d-flex align-items-center mb-3">
                <div class="bg-white text-primary icon-box shadow-sm me-3">
                    <i class="bi bi-hdd-network-fill"></i>
                </div>
                <h5 class="mb-0 fw-bold text-white bangla-text">বিটিআরসি (<span class="english-text">BTRC</span>) লগ সার্ভার নীতি</h5>
            </div>
            <div class="text-white bangla-text" style="font-size: 0.95rem; line-height: 1.8; opacity: 0.9;">
                <p class="mb-3">বাংলাদেশ টেলিযোগাযোগ নিয়ন্ত্রণ কমিশন (বিটিআরসি)-এর বিধিমালা অনুযায়ী, সকল আইএসপি-র জন্য নির্দিষ্ট সময় পর্যন্ত ইন্টারনেট ব্যবহারের লগ সংরক্ষণ করা বাধ্যতামূলক।</p>
                <div class="d-flex align-items-start mt-3 p-3 rounded" style="background: rgba(255,255,255,0.1);">
                    <i class="bi bi-info-circle-fill mt-1 me-3 fs-5"></i>
                    <p class="mb-0">জাতীয় নিরাপত্তা এবং নিয়ন্ত্রক সংস্থার নিয়ম পালনের জন্য আপনার ব্রাউজিং সেশন লগ, আইপি এবং ম্যাক অ্যাড্রেস আমাদের স্বয়ংক্রিয় লগ সার্ভারে নিরাপদে সংরক্ষিত থাকে।</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Middle Section: Router Setup -->
<div class="card stylish-card border border-slate-100 mb-4">
    <div class="card-header bg-white border-bottom border-slate-100 py-3">
        <h5 class="mb-0 fw-bold text-slate-800 bangla-text"><i class="bi bi-router-fill text-slate-400 me-2"></i>রাউটার সেটআপ নির্দেশিকা</h5>
    </div>
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-7">
                <ol class="text-slate-600 mb-0 bangla-text" style="line-height: 2; font-size: 0.95rem;">
                    <li><strong>ক্যাবল সংযোগ করুন:</strong> আপনার রাউটারের <span class="english-text">WAN</span> পোর্টে প্রধান ইন্টারনেট (<span class="english-text">WAN</span>) ক্যাবলটি যুক্ত করুন।</li>
                    <li><strong>পিসির সাথে সংযোগ করুন:</strong> রাউটার থেকে কম্পিউটারে একটি ল্যান ক্যাবল যুক্ত করুন অথবা ডিফল্ট ওয়াইফাই নেটওয়ার্কে যুক্ত হোন।</li>
                    <li><strong>রাউটার প্যানেলে প্রবেশ করুন:</strong> আপনার ব্রাউজার খুলুন এবং ডিফল্ট গেটওয়েতে যান (সাধারণত <span class="english-text">192.168.0.1</span> অথবা <span class="english-text">192.168.1.1</span>)।</li>
                    <li><strong><span class="english-text">PPPoE</span> নির্বাচন করুন:</strong> ইন্টারনেট সেটআপ অপশনে কানেকশন টাইপ হিসেবে <span class="english-text">PPPoE</span> নির্বাচন করুন।</li>
                    <li><strong>লগইন তথ্য দিন:</strong> সাপোর্ট টিম থেকে দেওয়া আপনার গোপন <span class="english-text">PPPoE Username</span> এবং <span class="english-text">Password</span> দিন।</li>
                    <li><strong>সেভ ও রিবুট করুন:</strong> সেটিংস সেভ করুন এবং ইন্টারনেট কানেকশন পেতে রাউটার রিস্টার্ট করুন।</li>
                </ol>
            </div>
            <div class="col-md-5 mt-4 mt-md-0">
                <div class="bg-slate-50 rounded p-4 text-center border border-slate-100">
                    <i class="bi bi-wifi text-slate-300 mb-2" style="font-size: 2.5rem;"></i>
                    <h6 class="fw-bold text-slate-700 bangla-text">কারিগরি সহায়তার প্রয়োজন?</h6>
                    <p class="text-slate-500 small mb-3 bangla-text">আপনার রাউটার কনফিগার করতে কোনো সমস্যা হলে, অনুগ্রহ করে আমাদের সাপোর্টে যোগাযোগ করুন।</p>
                    <a href="tel:09647077720" class="btn btn-primary btn-sm px-4 rounded-pill bangla-text"><i class="bi bi-telephone-fill me-1"></i> সাপোর্টে কল করুন: <span class="english-text">09647077720</span></a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Section: Terms & Privacy -->
<div class="row g-4">
    <!-- Terms and Conditions -->
    <div class="col-md-6">
        <div class="card stylish-card h-100 border border-slate-100">
            <div class="card-header bg-white border-bottom border-slate-100 py-3">
                <h5 class="mb-0 fw-bold text-slate-800 bangla-text"><i class="bi bi-file-earmark-text-fill text-slate-400 me-2"></i>শর্তাবলী (<span class="english-text">Terms & Conditions</span>)</h5>
            </div>
            <div class="card-body p-4 policy-content bangla-text">
                <h6>১. সার্ভিস ব্যবহার</h6>
                <p>প্রদানকৃত ইন্টারনেট সংযোগটি শুধুমাত্র ব্যক্তিগত বা নির্দিষ্ট প্রাতিষ্ঠানিক ব্যবহারের জন্য। পূর্বানুমতি ছাড়া সংযোগটি পুনরায় বিক্রি বা শেয়ার করা সম্পূর্ণ নিষিদ্ধ।</p>
                
                <h6>২. বিলিং এবং পেমেন্ট</h6>
                <p>প্রতি মাসে বিল তৈরি করা হয়। নির্ধারিত সময়ের মধ্যে বিল পরিশোধ করতে ব্যর্থ হলে সংযোগ সাময়িকভাবে বিচ্ছিন্ন করা হবে।</p>
                
                <h6>৩. ফেয়ার ইউসেজ পলিসি (<span class="english-text">FUP</span>)</h6>
                <p>যদিও আমরা আনলিমিটেড ইন্টারনেট প্রদান করি, অতিরিক্ত ব্যান্ডউইথ ব্যবহার যা অন্য গ্রাহকদের নেটওয়ার্ক অভিজ্ঞতায় ব্যাঘাত ঘটায়, সেক্ষেত্রে আমাদের ফেয়ার ইউসেজ পলিসি প্রযোজ্য হতে পারে।</p>
            </div>
        </div>
    </div>
    
    <!-- Privacy Policy -->
    <div class="col-md-6">
        <div class="card stylish-card h-100 border border-slate-100">
            <div class="card-header bg-white border-bottom border-slate-100 py-3">
                <h5 class="mb-0 fw-bold text-slate-800 bangla-text"><i class="bi bi-shield-lock-fill text-slate-400 me-2"></i>গোপনীয়তা নীতি (<span class="english-text">Privacy Policy</span>)</h5>
            </div>
            <div class="card-body p-4 policy-content bangla-text">
                <h6>১. ডেটা সংগ্রহ</h6>
                <p>নিরবচ্ছিন্ন সাপোর্ট প্রদান এবং পরিচয় যাচাইয়ের জন্য আমরা নিবন্ধনের সময় প্রয়োজনীয় ব্যক্তিগত তথ্য (নাম, ফোন, ঠিকানা, এনআইডি) সংগ্রহ করি।</p>
                
                <h6>২. ডেটা নিরাপত্তা</h6>
                <p>আপনার ব্যক্তিগত ডেটা এনক্রিপ্ট করে নিরাপদে সংরক্ষণ করা হয়। আমরা কোনো থার্ড-পার্টি মার্কেটিং এজেন্সির কাছে আপনার তথ্য বিক্রি বা শেয়ার করি না।</p>
                
                <h6>৩. আইনি প্রকাশ</h6>
                <p>বিটিআরসি এবং আইন প্রয়োগকারী সংস্থার নির্দেশ অনুযায়ী, জাতীয় নিরাপত্তা বা আইনি তদন্তের স্বার্থে আমরা আপনার ব্যক্তিগত তথ্য এবং ব্যবহারের লগ প্রকাশ করতে পারি।</p>
            </div>
        </div>
    </div>
</div>
</div>
<?php include 'includes/footer.php'; ?>