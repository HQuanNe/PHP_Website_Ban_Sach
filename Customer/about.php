<?php
/**
 * about.php — Trang Giới thiệu
 */
if (session_status() === PHP_SESSION_NONE) session_start();
?>

<style>
.about-container {
    max-width: 900px;
    margin: 40px auto;
    padding: 40px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.06);
    color: var(--text-color);
}
.about-header {
    text-align: center;
    margin-bottom: 40px;
}
.about-header h1 {
    font-size: 32px;
    font-weight: 800;
    color: var(--primary-color);
    margin-bottom: 16px;
}
.about-header p {
    font-size: 16px;
    color: var(--text-muted);
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}
.about-content {
    line-height: 1.8;
    font-size: 16px;
}
.about-content p {
    margin-bottom: 20px;
}
.team-section {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid var(--border-color);
}
.team-section h2 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 20px;
    text-align: center;
}
.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 30px;
}
.team-member {
    text-align: center;
    padding: 20px;
    background: var(--bg-color);
    border-radius: 12px;
    transition: transform 0.3s;
}
.team-member:hover {
    transform: translateY(-5px);
}
.member-avatar {
    width: 80px;
    height: 80px;
    background: var(--primary-light);
    color: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin: 0 auto 16px;
}
.member-name {
    font-weight: 700;
    font-size: 16px;
    margin-bottom: 4px;
}
.member-role {
    font-size: 13px;
    color: var(--text-muted);
}
.disclaimer {
    margin-top: 40px;
    padding: 20px;
    background: #fff3e0;
    border-left: 4px solid #ff9800;
    border-radius: 8px;
    font-size: 15px;
}
</style>

<div class="about-container">
    <div class="about-header">
        <h1>Về Chúng Tôi</h1>
        <p>Chào mừng bạn đến với Dream Book - Nơi lan tỏa niềm đam mê đọc sách.</p>
    </div>
    
    <div class="about-content">
        <p><strong>Dream Book</strong> là một dự án website thương mại điện tử chuyên cung cấp các loại sách đa dạng, từ văn học, kỹ năng sống, đến sách chuyên ngành và thiếu nhi. Sứ mệnh của chúng tôi là mang những cuốn sách hay nhất đến tận tay người đọc một cách nhanh chóng và tiện lợi nhất.</p>
        
        <p>Dự án này được xây dựng và phát triển bởi một nhóm gồm <strong>5 bạn sinh viên</strong> với niềm đam mê lớn dành cho công nghệ thông tin và sách. Trong quá trình học tập và làm việc nhóm, chúng tôi đã cùng nhau nghiên cứu, thiết kế, và lập trình để tạo ra một nền tảng trực tuyến thân thiện, dễ sử dụng.</p>

        <div class="team-section">
            <h2>Đội ngũ phát triển</h2>
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-avatar"><i class="fa-solid fa-user-graduate"></i></div>
                    <div class="member-name">Thành viên 1</div>
                    <div class="member-role">Nhóm trưởng / Fullstack</div>
                </div>
                <div class="team-member">
                    <div class="member-avatar"><i class="fa-solid fa-user-graduate"></i></div>
                    <div class="member-name">Thành viên 2</div>
                    <div class="member-role">Frontend Developer</div>
                </div>
                <div class="team-member">
                    <div class="member-avatar"><i class="fa-solid fa-user-graduate"></i></div>
                    <div class="member-name">Thành viên 3</div>
                    <div class="member-role">Backend Developer</div>
                </div>
                <div class="team-member">
                    <div class="member-avatar"><i class="fa-solid fa-user-graduate"></i></div>
                    <div class="member-name">Thành viên 4</div>
                    <div class="member-role">Database / Tester</div>
                </div>
                <div class="team-member">
                    <div class="member-avatar"><i class="fa-solid fa-user-graduate"></i></div>
                    <div class="member-name">Thành viên 5</div>
                    <div class="member-role">UI/UX Designer</div>
                </div>
            </div>
        </div>

        <div class="disclaimer">
            <strong>Lời ngỏ:</strong><br>
            Vì đây là một dự án học tập do nhóm sinh viên thực hiện, kinh nghiệm và trải nghiệm thực tế vẫn còn nhiều hạn chế. Do đó, một số chức năng trên website có thể chưa được hoàn thiện 100% hoặc còn gặp lỗi trong quá trình sử dụng.<br><br>
            Chúng tôi rất mong nhận được sự thông cảm và những ý kiến đóng góp quý báu từ bạn để có thể cải thiện website ngày càng tốt hơn. Bạn có thể gửi góp ý cho chúng tôi tại trang <a href="index.php?layout=feedback" style="color:#ff9800; font-weight:bold; text-decoration:underline;">Feedback</a>. Chân thành cảm ơn!
        </div>
    </div>
</div>
