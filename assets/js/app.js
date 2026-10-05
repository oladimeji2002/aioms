/* =====================================================================
   REAL API LAYER
   ===================================================================== */

const API_BASE = "api";

async function apiRequest(endpoint, method = "GET", data = null) {

  const options = {
    method,
    credentials: "include",
    headers: {
      "Accept": "application/json"
    }
  };

  let url = `${API_BASE}${endpoint}`;

  /*
  |--------------------------------------------------------------------------
  | GET / HEAD
  |--------------------------------------------------------------------------
  | Send data as query parameters, never as a request body.
  |--------------------------------------------------------------------------
  */

  if (
    (method === "GET" || method === "HEAD") &&
    data &&
    typeof data === "object"
  ) {

    const params = new URLSearchParams();

    Object.entries(data).forEach(([key, value]) => {

      if (
        value !== undefined &&
        value !== null &&
        value !== ""
      ) {
        params.append(key, value);
      }

    });

    const query = params.toString();

    if (query) {
      url += `?${query}`;
    }
  }

  /*
  |--------------------------------------------------------------------------
  | POST / PUT / PATCH / DELETE
  |--------------------------------------------------------------------------
  */

  else if (
    data !== null &&
    ["POST", "PUT", "PATCH", "DELETE"].includes(method)
  ) {

    options.headers["Content-Type"] =
      "application/json";

    options.body =
      JSON.stringify(data);
  }

  try {

    const response =
      await fetch(url, options);

    const text =
      await response.text();

    let result;

    try {

      result =
        text ? JSON.parse(text) : {};

    } catch (e) {

      console.error(
        "Invalid JSON response:",
        text
      );

      return {
        success: false,
        message:
          "Server returned an invalid response."
      };
    }

    if (!response.ok) {

      console.error(
        "API error:",
        result
      );

      return {
        ...result,
        success: false
      };
    }

    return result;

  } catch (error) {

    console.error(
      "API request failed:",
      error
    );

    return {
      success: false,
      message:
        "Unable to connect to the server."
    };
  }
}


const API = {

  /* =========================
     AUTH
     ========================= */

loginUser: (email, password) =>
  apiRequest(
    "/auth/login.php",
    "POST",
    {
      email,
      password
    }
  ),

  logoutUser: () =>
    apiRequest("/auth/logout.php", "POST"),


  /* =========================
     STUDENT
     ========================= */

  getStudentDashboard: () =>
    apiRequest("/students/dashboard.php", "GET"),

  registerStudent: (data) =>
    apiRequest("/auth/register.php", "POST", data),

  getStudentProfile: () =>
    apiRequest("/students/profile.php", "GET"),

  updateStudentProfile: (data) =>
    apiRequest("/students/update-profile.php", "POST", data),


  /* =========================
     APPLICATION
     ========================= */

  submitApplication: (data) =>
    apiRequest("/students/application.php", "POST", data),

  getApplication: () =>
    apiRequest("/students/application.php", "GET"),


  /* =========================
     AI PLACEMENT
     ========================= */

  getAIPlacementRecommendation: (applicationId) =>
    apiRequest("/ai/analyze-placement.php", "POST", {
    }),

  getPlacementRecommendations: () =>
    apiRequest("/students/placement-recommendations.php", "GET"),

  registerSupervisor: (data) =>
  apiRequest(
    "/auth/register-supervisor.php",
    "POST",
    data
  ),


  /* =========================
     PLACEMENT
     ========================= */

  getPlacement: () =>
    apiRequest("/students/placement.php", "GET"),

  acceptPlacement: (placementId) =>
    apiRequest("/students/accept-placement.php", "POST", {
    }),

  getPendingPlacements: () =>
  apiRequest(
    "/supervisor/pending-placements.php",
    "GET"
  ),

  supervisorPlacementAction: (placementId, action, reason = "") =>
  apiRequest(
    "/supervisor/placement-action.php",
    "POST",
    {
      placement_id: Number(placementId),
      action,
      reason
    }
  ),

  getStudentProfile: () =>
    apiRequest(
      "/students/profile.php",
      "GET"
    ),

  /* =========================
     WEEKLY REPORTS / LOGBOOK
     ========================= */

  getWeeklyReports: () =>
    apiRequest("/students/weekly-reports.php", "GET"),

  submitWeeklyReport: (report) =>
    apiRequest("/students/weekly-reports.php", "POST", report),

  getWeeklyReport: (reportId) =>
    apiRequest("/students/weekly-report-view.php", "GET", {
      report_id: reportId
    }),

  getLogbook: () =>
    apiRequest("/students/logbook.php", "GET"),

  
  getSupervisorWeeklyReports: () =>
  apiRequest(
    "/supervisor/weekly-reports.php",
    "GET"
  ),

  getSupervisorWeeklyReport: (reportId) =>
    apiRequest(
      "/supervisor/weekly-report-view.php",
      "GET",
      {
        id: reportId
      }
    ),

  reviewSupervisorWeeklyReport: (
    reportId,
    action,
    supervisorComment = ""
  ) =>
    apiRequest(
      "/supervisor/review-report.php",
      "POST",
      {
        report_id: Number(reportId),
        action,
        supervisor_comment: supervisorComment
      }
    ),
    ////////////////DASHBOARD///////////////
    getSupervisorDashboard: () =>
      apiRequest(
        "/supervisor/dashboard.php",
        "GET"
    ),

  /* =========================
     AI REPORT ANALYSIS
     ========================= */

  analyzeWeeklyReport: (reportId) =>
    apiRequest("/ai/analyze-report.php", "POST", {
      report_id: reportId
    }),

  getReportAnalysis: (reportId) =>
    apiRequest("/students/report-analysis.php", "GET", {
      report_id: reportId
    }),


  /* =========================
     FINAL EVALUATION
     ========================= */

  submitSupervisorEvaluation: (data) =>
  apiRequest(
    "/evaluations/create.php",
    "POST",
    data
  ),

generateAIFinalEvaluation: (placementId) =>
  apiRequest(
    "/ai/final-evaluation.php",
    "POST",
    {
      placement_id: Number(placementId)
    }
  ),

    getStudentFinalEvaluation: () =>
  apiRequest(
    "/students/final-evaluation.php",
    "GET"
  ),

  /* =========================
     NOTIFICATIONS
     ========================= */

  getNotifications: () =>
    apiRequest("/notifications/list.php", "GET"),

  getUnreadNotificationCount: () =>
    apiRequest("/notifications/unread-count.php", "GET"),

  markNotificationRead: (notificationId) =>
    apiRequest("/notifications/mark-read.php", "POST", {
      notification_id: notificationId
    })

};
function restoreLoginState() {

  try {

    const savedUser =
      localStorage.getItem("AI-ITMS_user");

    const savedStudent =
      localStorage.getItem("AI-ITMS_student");

    const savedRole =
      localStorage.getItem("AI-ITMS_role");

    if (!savedUser || !savedRole) {
      return;
    }

    state.currentUser =
      JSON.parse(savedUser);

    state.currentRole =
      savedRole;

    if (savedStudent) {
      state.currentStudent =
        JSON.parse(savedStudent);
    }

    console.log(
      "🔄 Restoring login:",
      state.currentRole,
      state.currentUser
    );

    enterPortalWithoutReplacingUser(
      state.currentRole
    );

  } catch (error) {

    console.error(
      "❌ Failed to restore login state:",
      error
    );

    localStorage.removeItem("AI-ITMS_user");
    localStorage.removeItem("AI-ITMS_student");
    localStorage.removeItem("AI-ITMS_role");
  }
}

function enterPortalWithoutReplacingUser(role) {

  document
    .getElementById("screen-auth")
    .classList.add("hidden");

  document
    .getElementById("screen-student")
    .classList.add("hidden");

  document
    .getElementById("screen-supervisor")
    .classList.add("hidden");

  const portal =
    document.getElementById(
      "screen-" + role
    );

  if (portal) {
    portal.classList.remove("hidden");
  }

  if (role === "student") {
    studentNav("dashboard");
  }

  if (role === "supervisor") {
    supervisorNav("dashboard");
  }
}

/* =====================================================================
   DUMMY DATA — mirrors the intended MySQL schema (see header comment).
   ===================================================================== */
const DB = {
  users: [
    {id:1, role:'student', name:'Daniel Okafor', initials:'DO', email:'daniel.okafor@futa.edu.ng'},
    {id:2, role:'supervisor', name:'Engr. Amaka Bello', initials:'AB', email:'amaka.bello@technova.ng'},
  ],
  departments: ['Computer Science','Software Engineering','Electrical/Electronics Engineering','Accounting','Business Administration','Mass Communication','Statistics','Office Technology and Management'],
  programmes: ['B.Sc Computer Science','B.Eng Software Engineering','B.Eng Electrical/Electronics Engineering','B.Sc Accounting','B.Sc Business Administration','B.Sc Mass Communication','B.Sc Statistics','HND Office Technology and Management'],
  skillsCatalog: ['Programming','Web Development','Database Management','Networking','UI/UX Design','Data Analysis','Cybersecurity','Cloud Computing','Accounting','Marketing','Electrical Systems','Content Writing'],
  careerCatalog: ['Software Engineering','Backend Development','Frontend Development','Cybersecurity','Data Science','Networking','Finance','Marketing','Renewable Energy Engineering','Broadcast Journalism'],

  student: {
    name:'Daniel Okafor', regNo:'CSC/2021/041', email:'daniel.okafor@futa.edu.ng', phone:'+234 803 214 7765',
    department:'Computer Science', programme:'B.Sc Computer Science', level:'300 Level',
    skills:['JavaScript','PHP','MySQL','Git','REST APIs'],
    careerInterests:['Backend Development','Software Engineering'],
    industryInterest:'Software Development', preferredLocation:'Lagos, Nigeria',
    previousExperience:'Built a student result-checking portal as a class project; 3-month freelance PHP work.',
    profileCompletion:85
  },

  placement: {
    org:'TechNova Solutions Ltd.', industry:'Software Development', location:'Lagos, Nigeria',
    role:'Backend Development Intern', supervisor:'Engr. Amaka Bello',
    start:'June 1, 2026', end:'August 10, 2026', duration:'10 weeks',
    status:'Active', currentWeek:6, totalWeeks:10
  },

  aiRecommendation: {
    org:'TechNova Solutions Ltd.', industry:'Software Development', location:'Lagos, Nigeria',
    role:'Backend Development Intern', match:92,
    reason:"TechNova's backend team works primarily in PHP and MySQL, which lines up directly with Daniel's coursework and self-taught skills. His stated interest in backend development and prior freelance PHP experience make this placement a strong technical and career fit.",
    factors:[
      {label:'Course compatibility', value:95},
      {label:'Skill compatibility', value:90},
      {label:'Career compatibility', value:93},
      {label:'Industry compatibility', value:88},
      {label:'Location compatibility', value:96},
    ],
    alternatives:[
      {org:'CodeCraft Africa', industry:'Software Development', location:'Lagos, Nigeria', role:'Junior Full-Stack Intern', match:84},
      {org:'ByteWave Systems', industry:'IT Consulting', location:'Lagos, Nigeria', role:'Web Development Intern', match:79},
      {org:'DataPrime Analytics', industry:'Data Services', location:'Ibadan, Nigeria', role:'Data Analyst Intern', match:74},
    ]
  },

  organizations: [
    {id:1, name:'TechNova Solutions Ltd.', industry:'Software Development', location:'Lagos, Nigeria', capacity:12, assigned:9, supervisor:'Engr. Amaka Bello', skills:['PHP','MySQL','JavaScript','REST APIs'], courses:['Computer Science','Software Engineering'], roles:['Backend Development Intern','Frontend Development Intern','QA Intern'], status:'Active', desc:'A Lagos-based software house building web and mobile platforms for fintech and logistics clients.'},
    {id:2, name:'CodeCraft Africa', industry:'Software Development', location:'Lagos, Nigeria', capacity:8, assigned:5, supervisor:'Mr. Tunde Salako', skills:['JavaScript','React','Node.js'], courses:['Computer Science','Software Engineering'], roles:['Full-Stack Intern'], status:'Active', desc:'A product studio building SaaS tools for African SMEs.'},
    {id:3, name:'ByteWave Systems', industry:'IT Consulting', location:'Lagos, Nigeria', capacity:6, assigned:4, supervisor:'Mrs. Ifeoma Nwachukwu', skills:['Networking','Cloud','Support'], courses:['Computer Science','Electrical/Electronics Engineering'], roles:['Web Development Intern','IT Support Intern'], status:'Active', desc:'Enterprise IT consulting and managed services provider.'},
    {id:4, name:'DataPrime Analytics', industry:'Data Services', location:'Ibadan, Nigeria', capacity:5, assigned:3, supervisor:'Dr. Kelechi Umeh', skills:['SQL','Python','Data Visualization'], courses:['Statistics','Computer Science'], roles:['Data Analyst Intern'], status:'Active', desc:'Analytics consultancy serving retail and telecom clients.'},
    {id:5, name:'GreenGrid Power Systems', industry:'Renewable Energy', location:'Abuja, Nigeria', capacity:6, assigned:6, supervisor:'Engr. Musa Bello', skills:['Electrical Systems','AutoCAD'], courses:['Electrical/Electronics Engineering'], roles:['Field Engineering Intern'], status:'Full', desc:'Solar mini-grid developer serving underserved communities.'},
    {id:6, name:'Zenith Capital Advisory', industry:'Finance', location:'Lagos, Nigeria', capacity:5, assigned:2, supervisor:'Mrs. Bisi Fashola', skills:['Accounting','Excel','Financial Modelling'], courses:['Accounting','Business Administration'], roles:['Finance Intern'], status:'Active', desc:'Investment advisory firm serving mid-market Nigerian companies.'},
    {id:7, name:'PrimeWave Media Group', industry:'Media & Broadcasting', location:'Lagos, Nigeria', capacity:4, assigned:3, supervisor:'Mr. Chuka Obi', skills:['Content Writing','Video Editing'], courses:['Mass Communication'], roles:['Broadcast Journalism Intern'], status:'Active', desc:'Radio, TV and digital media production house.'},
  ],

  weeklyReports: [
    {week:1, range:'Jun 1 – Jun 5', hours:38, status:'Approved', activities:'Onboarding, dev environment setup, introduced to the internal PHP MVC framework and coding standards.', skills:'Git workflow, internal framework conventions', challenges:'Unfamiliar folder structure in the framework', solution:'Pair-programmed with a senior dev for two sessions', tools:'PHP, Git, VS Code, XAMPP', outcome:'Comfortable navigating and running the codebase locally.', ai:{relevance:88, quality:80, skill:75, flags:['Report is a little brief for a first week']}},
    {week:2, range:'Jun 8 – Jun 12', hours:40, status:'Approved', activities:'Built and tested three REST endpoints for the customer-accounts module.', skills:'REST API design, input validation', challenges:'Inconsistent error-response format across the codebase', solution:'Proposed and got approval for a shared error-handler class', tools:'PHP, MySQL, Postman', outcome:'Endpoints merged after code review with minor changes.', ai:{relevance:93, quality:87, skill:85, flags:['Strong ownership shown in proposing the error-handler fix']}},
    {week:3, range:'Jun 15 – Jun 19', hours:39, status:'Approved', activities:'Optimised slow queries on the transactions table; added indexes.', skills:'Query optimisation, EXPLAIN plans, indexing', challenges:'Two queries had unclear index needs', solution:'Reviewed with DB lead; added a composite index', tools:'MySQL, phpMyAdmin', outcome:'Reduced average query time on the reports page from 1.8s to 240ms.', ai:{relevance:95, quality:90, skill:91, flags:[]}},
    {week:4, range:'Jun 22 – Jun 26', hours:36, status:'Approved', activities:'Implemented JWT-based auth for the internal admin API.', skills:'Authentication, JWT, middleware design', challenges:'Token refresh edge cases', solution:'Wrote unit tests to cover refresh/expiry paths', tools:'PHP, JWT library, PHPUnit', outcome:'Auth module passed QA with no reopened bugs.', ai:{relevance:92, quality:89, skill:90, flags:[]}},
    {week:5, range:'Jun 29 – Jul 3', hours:40, status:'Approved', activities:'Wrote integration tests for the payments module and fixed 4 bugs found in the process.', skills:'Integration testing, debugging', challenges:'Flaky test due to a race condition', solution:'Added a proper wait/lock instead of a fixed delay', tools:'PHPUnit, MySQL', outcome:'Payments module test coverage rose from 40% to 72%.', ai:{relevance:90, quality:85, skill:88, flags:['Consistent weekly hours logged']}},
    {week:6, range:'Jul 6 – Jul 10', hours:22, status:'Pending Review', activities:'Started building the notifications microservice; drafted the schema and queued the first worker job.', skills:'Service design, message queues', challenges:'Choosing between polling and a queue-based approach', solution:'Went with Redis-backed queue after discussing trade-offs with the team lead', tools:'PHP, Redis, MySQL', outcome:'Schema approved; first worker job runs end-to-end in dev.', ai:null},
  ],

  attendance: [
    {date:'Jul 6, 2026', checkin:'8:02 AM', checkout:'5:04 PM', hours:8.0, status:'Present'},
    {date:'Jul 7, 2026', checkin:'8:11 AM', checkout:'5:00 PM', hours:7.8, status:'Present'},
    {date:'Jul 8, 2026', checkin:'—', checkout:'—', hours:0, status:'Absent'},
    {date:'Jul 9, 2026', checkin:'9:20 AM', checkout:'5:10 PM', hours:6.8, status:'Late'},
    {date:'Jul 10, 2026', checkin:'8:05 AM', checkout:'5:02 PM', hours:8.0, status:'Present'},
  ],

  finalEvaluation: {
    overall:84,
    breakdown:[
      {label:'Technical Development', value:86},
      {label:'Activity Relevance', value:90},
      {label:'Learning Progress', value:84},
      {label:'Report Quality', value:82},
      {label:'Consistency', value:78},
      {label:'Professional Development', value:85},
    ],
    summary:"Daniel completed all ten weeks of his placement at TechNova Solutions with activities that stayed closely aligned to the Backend Development Intern role he was matched to. His technical output progressed from routine setup tasks to independently designing a queue-backed microservice, and his weekly reports consistently documented real engineering decisions rather than generic task lists. Report consistency dipped slightly around week 6, coinciding with a lighter-hours week, but recovered by week 8.",
    strengths:['Clear month-over-month growth in backend engineering ability','Proactively proposed and implemented the shared error-handler fix in week 2','Test coverage contributions materially improved the payments module','Communicates technical trade-offs clearly in weekly reports'],
    improvements:['Week 6 report was noticeably shorter than the rest — keep report depth consistent even in lighter weeks','Could engage more with code-review comments from teammates, based on supervisor notes'],
    skills:['PHP','MySQL','REST API Design','JWT Authentication','Redis Queues','PHPUnit Testing','Query Optimisation','Git'],
    recommendation:'Student successfully completed training. Demonstrated strong technical development and is well-suited for further growth in backend engineering roles; a follow-on placement or graduate role in backend/platform engineering is recommended.'
  },

  notifications: [
    {id:1, for:'student', title:'Week 6 report received', msg:'Your Week 6 report is awaiting supervisor review.', time:'2 hours ago', icon:'clipboard-list', tone:'info'},
    {id:2, for:'student', title:'Report approved', msg:'Engr. Amaka Bello approved your Week 5 report.', time:'Yesterday', icon:'check-circle-2', tone:'success'},
    {id:3, for:'student', title:'AI analysis complete', msg:'AI finished analysing your Week 5 report — 90% quality score.', time:'Yesterday', icon:'sparkles', tone:'ai'},
    {id:4, for:'student', title:'Placement approved', msg:'Your placement at TechNova Solutions Ltd. was approved by your supervisor.', time:'5 weeks ago', icon:'building-2', tone:'success'},
    {id:5, for:'supervisor', title:'New report submitted', msg:'Daniel Okafor submitted a Week 6 report.', time:'2 hours ago', icon:'clipboard-list', tone:'info'},
    {id:6, for:'supervisor', title:'Student requires attention', msg:'Ngozi Eze has 2 late attendance flags this week.', time:'1 day ago', icon:'alert-triangle', tone:'warning'},
    {id:7, for:'supervisor', title:'Placement awaiting approval', msg:'2 AI-recommended placements are pending your approval.', time:'3 hours ago', icon:'shuffle', tone:'warning'},
    {id:8, for:'supervisor', title:'New student registration', msg:'Ifeanyi Chukwu registered and completed profile setup.', time:'1 day ago', icon:'user-plus', tone:'info'},
    {id:9, for:'supervisor', title:'Student flagged by AI', msg:'AI flagged Musa Ibrahim — 2 consecutive reports below quality threshold.', time:'2 days ago', icon:'flag', tone:'danger'},
  ],

  pendingPlacements: [
    {student:'Fatima Bello', course:'Computer Science', org:'CodeCraft Africa', role:'Full-Stack Intern', match:81, reason:'Frontend coursework and stated interest in UI/UX line up with this full-stack intern opening.', date:'Aug 10, 2026'},
    {student:'Tobi Lawal', course:'Computer Science', org:'TechNova Solutions Ltd.', role:'QA Intern', match:76, reason:'Networking elective partially matches this QA/support-leaning role; worth a closer look before confirming.', date:'Aug 12, 2026'},
  ],

  supervisorStudents: [
    {name:'Daniel Okafor', initials:'DO', programme:'B.Sc Computer Science', org:'TechNova Solutions Ltd.', week:6, attendance:96, latest:'Week 6 — Pending', ai:88, status:'On Track'},
    {name:'Ngozi Eze', initials:'NE', programme:'B.Sc Computer Science', org:'TechNova Solutions Ltd.', week:6, attendance:81, latest:'Week 6 — Approved', ai:74, status:'Needs Attention'},
    {name:'Ifeanyi Chukwu', initials:'IC', programme:'B.Eng Software Engineering', org:'TechNova Solutions Ltd.', week:5, attendance:93, latest:'Week 5 — Approved', ai:91, status:'On Track'},
    {name:'Musa Ibrahim', initials:'MI', programme:'B.Sc Computer Science', org:'TechNova Solutions Ltd.', week:6, attendance:88, latest:'Week 6 — Correction Requested', ai:61, status:'At Risk'},
    {name:'Chiamaka Obi', initials:'CO', programme:'B.Eng Software Engineering', org:'TechNova Solutions Ltd.', week:4, attendance:97, latest:'Week 4 — Approved', ai:85, status:'On Track'},
    {name:'Tobi Lawal', initials:'TL', programme:'B.Sc Computer Science', org:'TechNova Solutions Ltd.', week:6, attendance:90, latest:'Week 6 — Pending', ai:79, status:'On Track'},
  ],

};

function sampleAIReportAnalysis(report){
  const base = report && report.week ? report.week : 6;
  return {
    relevance: 84 + (base%4),
    quality: 80 + (base%5),
    skill: 78 + (base%6),
    consistency: 88,
    flags: base===6
      ? ['Report is shorter than your recent average — consider adding more detail on the challenge you solved.', 'Activities clearly match your Backend Development Intern role.']
      : ['Activities clearly match your assigned role.', 'Skill development is trending upward week over week.'],
    summary: `Week ${base} activities are strongly related to the assigned backend development role, with a clear challenge, solution and measurable outcome documented.`
  };
}

/* =====================================================================
   APP STATE + CORE HELPERS
   ===================================================================== */
const state = {
  currentUser: null,
  currentRole: 'student',
  theme: 'light',
  selectedWeek: 6,
  applicationStep: 1,
  applicationData: {skills:[], careers:[]},
};

function icons(){ if(window.lucide) lucide.createIcons(); }

function toggleTheme(){
  state.theme = state.theme === 'light' ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', state.theme);
  ['Student','Supervisor'].forEach(p=>{
    const el = document.getElementById('themeIcon'+p);
    if(el) el.setAttribute('data-lucide', state.theme==='light' ? 'moon' : 'sun');
  });
  icons();
}

function toggleSidebar(portal, open){
  document.getElementById(portal+'Sidebar').classList.toggle('open', open);
  document.getElementById(portal+'Overlay').classList.toggle('open', open);
}

function showToast({title, msg, tone='info', icon='info'}){
  const toneMap = {
    info:{bg:'var(--info-tint)', color:'var(--info)'},
    success:{bg:'var(--success-tint)', color:'var(--success)'},
    warning:{bg:'var(--warning-tint)', color:'var(--warning)'},
    danger:{bg:'var(--danger-tint)', color:'var(--danger)'},
    ai:{bg:'var(--ai-tint)', color:'var(--ai-1)'},
  };
  const c = toneMap[tone] || toneMap.info;
  const stack = document.getElementById('toastStack');
  const el = document.createElement('div');
  el.className = 'toast';
  el.innerHTML = `
    <div class="t-icon" style="background:${c.bg};color:${c.color};"><i data-lucide="${icon}"></i></div>
    <div><div class="t-title">${title}</div><div class="t-msg">${msg||''}</div></div>
    <button class="toast-close" onclick="this.closest('.toast').remove()"><i data-lucide="x" style="width:14px;height:14px;"></i></button>`;
  stack.appendChild(el);
  icons();
  setTimeout(()=>{ if(el.parentNode) el.remove(); }, 5500);
}

function openModal(html, size=''){
  const root = document.getElementById('modalRoot');
  root.innerHTML = `<div class="modal-overlay" id="activeModalOverlay" onclick="if(event.target===this)closeModal()">
      <div class="modal ${size}">${html}</div>
    </div>`;
  icons();
}
function closeModal(){ document.getElementById('modalRoot').innerHTML=''; }

/* =====================================================================
   AUTH FLOW
   ===================================================================== */
function selectRole(role) {

  document.querySelectorAll(".role-opt").forEach(option => {
    option.classList.remove("selected");
  });

  const selected = document.querySelector(
    `.role-opt[data-role="${role}"]`
  );

  if (selected) {
    selected.classList.add("selected");
  }

  console.log("Selected role:", role);
}

async function handleLogin(e) {

  e.preventDefault();

  console.log(
    "🔥 HANDLE LOGIN FIRED"
  );


  const email =
    document
      .getElementById("loginEmail")
      ?.value
      .trim() || "";


  const password =
    document
      .getElementById("loginPassword")
      ?.value || "";


  if (!email || !password) {

    showToast({
      title: "Login failed",
      msg:
        "Please enter your email and password.",
      tone: "danger",
      icon: "alert-circle"
    });

    return;
  }


  const btn =
    document.getElementById(
      "loginSubmitBtn"
    );


  try {

    btn.disabled = true;

    btn.innerHTML =
      '<span class="spinner"></span>';


    const res =
      await API.loginUser(
        email,
        password
      );


    console.log(
      "🔥 LOGIN RESPONSE:",
      res
    );


    if (
      !res ||
      !res.success
    ) {

      showToast({
        title: "Login failed",
        msg:
          res?.message ||
          "Invalid login details.",
        tone: "danger",
        icon: "alert-circle"
      });

      return;
    }


    /*
    |--------------------------------------------------------------------------
    | ROLE COMES FROM SERVER
    |--------------------------------------------------------------------------
    */

    const serverRole =
      res?.user?.role;


    if (
      serverRole !== "student" &&
      serverRole !== "supervisor"
    ) {

      throw new Error(
        "Unable to determine account role."
      );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE USER
    |--------------------------------------------------------------------------
    */

    state.currentUser =
      res.user || {};


    state.currentRole =
      serverRole;


    localStorage.setItem(
      "AI-ITMS_user",
      JSON.stringify(
        state.currentUser
      )
    );


    localStorage.setItem(
      "AI-ITMS_role",
      serverRole
    );


    /*
    |--------------------------------------------------------------------------
    | STORE STUDENT DATA
    |--------------------------------------------------------------------------
    */

    if (
      serverRole === "student" &&
      res.student
    ) {

      state.currentStudent =
        res.student;


      localStorage.setItem(
        "AI-ITMS_student",
        JSON.stringify(
          res.student
        )
      );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE TOP NAVBAR
    |--------------------------------------------------------------------------
    */

    updateTopbarUser(
      res.user,
      serverRole,
      res.student || null
    );


    console.log(
      "🎯 LOGGING IN AS:",
      serverRole
    );


    /*
    |--------------------------------------------------------------------------
    | ENTER CORRECT PORTAL
    |--------------------------------------------------------------------------
    */

    enterPortal(
      serverRole
    );


  } catch (error) {

    console.error(
      "🔥 LOGIN ERROR:",
      error
    );


    showToast({
      title:
        "Connection error",

      msg:
        error?.message ||
        "Unable to connect to the server.",

      tone:
        "danger",

      icon:
        "wifi-off"
    });


  } finally {

    btn.disabled = false;

    btn.innerHTML =
      `
        <span class="btn-label">
          Sign in
        </span>
      `;
  }
}


function enterPortal(role) {

  state.currentRole = role;

  const authScreen =
    document.getElementById("screen-auth");

  const studentScreen =
    document.getElementById("screen-student");

  const supervisorScreen =
    document.getElementById("screen-supervisor");

  if (authScreen) {
    authScreen.classList.add("hidden");
  }

  if (studentScreen) {
    studentScreen.classList.add("hidden");
  }

  if (supervisorScreen) {
    supervisorScreen.classList.add("hidden");
  }

  const target =
    document.getElementById(
      "screen-" + role
    );

  if (!target) {

    console.error(
      "Portal screen not found for role:",
      role
    );

    return;
  }

  target.classList.remove("hidden");

  /*
  |--------------------------------------------------------------------------
  | Load the correct portal
  |--------------------------------------------------------------------------
  */

  if (role === "student") {
    studentNav("dashboard");
  }

  if (role === "supervisor") {
    supervisorNav("dashboard");
  }

  /*
  |--------------------------------------------------------------------------
  | Use the REAL logged-in user.
  | Do NOT replace it with DB.users.
  |--------------------------------------------------------------------------
  */

  const userName =
    state.currentUser?.full_name ||
    state.currentUser?.name ||
    "User";

  showToast({
    title:
      "Welcome back, " +
      userName.split(" ")[0] +
      "!",
    msg:
      "Signed in as " + role + ".",
    tone: "success",
    icon: "log-in"
  });
}

async function logout() {

  try {

    await API.logoutUser();

  } catch (error) {

    console.error(
      "Logout API error:",
      error
    );

  } finally {

    localStorage.removeItem("AI-ITMS_user");
    localStorage.removeItem("AI-ITMS_student");
    localStorage.removeItem("AI-ITMS_role");

    state.currentUser = null;
    state.currentStudent = null;
    state.currentRole = null;

    document
      .getElementById("screen-student")
      .classList.add("hidden");

    document
      .getElementById("screen-supervisor")
      .classList.add("hidden");

    document
      .getElementById("screen-auth")
      .classList.remove("hidden");

    console.log("👋 Logged out");
  }
}

function openRegisterModal() {

  openModal(`

    <div class="modal-head">

      <h3 class="card-title">
        Create student account
      </h3>

      <button
        class="icon-btn"
        onclick="closeModal()"
      >
        <i data-lucide="x"></i>
      </button>

    </div>


    <div class="modal-body">

      <div class="field">

        <label>
          Full name
        </label>

        <input
          class="input"
          id="regFullName"
          type="text"
          placeholder="e.g. Chidinma Okoro"
          required
        >

      </div>


      <div class="grid grid-2">

        <div class="field">

          <label>
            Matric number
          </label>

          <input
            class="input"
            id="regMatricNo"
            type="text"
            placeholder="CSC/2022/103"
            required
          >

        </div>


        <div class="field">

          <label>
            Department
          </label>

          <select
            class="input"
            id="regDepartment"
            required
          >

            <option value="">
              Select department
            </option>

            ${DB.departments
              .map(d => `
                <option value="${escapeHtml(d)}">
                  ${escapeHtml(d)}
                </option>
              `)
              .join("")}

          </select>

        </div>


        <div class="field">

          <label>
            Programme
          </label>

          <select
            class="input"
            id="regProgramme"
            required
          >

            <option value="">
              Select programme
            </option>

            ${DB.programmes
              .map(p => `
                <option value="${escapeHtml(p)}">
                  ${escapeHtml(p)}
                </option>
              `)
              .join("")}

          </select>

        </div>


        <div class="field">

          <label>
            Level
          </label>

          <select
            class="input"
            id="regLevel"
            required
          >

            <option value="">
              Select level
            </option>

            <option value="200 Level">
              200 Level
            </option>

            <option value="300 Level">
              300 Level
            </option>

            <option value="400 Level">
              400 Level
            </option>

            <option value="HND 1">
              HND 1
            </option>

            <option value="HND 2">
              HND 2
            </option>

          </select>

        </div>

      </div>


      <div class="field">

        <label>
          Email address
        </label>

        <input
          class="input"
          id="regEmail"
          type="email"
          placeholder="you@example.edu.ng"
          required
        >

      </div>


      <div class="field">

        <label>
          Password
        </label>

        <input
          class="input"
          id="regPassword"
          type="password"
          placeholder="At least 6 characters"
          required
        >

      </div>


      <div class="field">

        <label>
          Phone
          <span class="cell-sub">
            Optional
          </span>
        </label>

        <input
          class="input"
          id="regPhone"
          type="text"
          placeholder="08012345678"
        >

      </div>

    </div>


    <div class="modal-foot">

      <button
        class="btn btn-outline"
        onclick="closeModal()"
      >
        Cancel
      </button>

      <button
        class="btn btn-primary"
        id="registerSubmitBtn"
        onclick="submitRegistration()"
      >
        Create account
      </button>

    </div>

  `);

  icons();
}

async function runSupervisorReportAI(
  button,
  reportId
) {

  if (!reportId) {

    showToast({
      title: "Invalid report",
      msg: "A valid report ID is required.",
      tone: "danger",
      icon: "alert-circle"
    });

    return;
  }


  try {

    if (button) {

      button.disabled = true;

      button.innerHTML =
        '<span class="spinner"></span> Analyzing...';
    }


    console.log(
      "🤖 Starting supervisor AI analysis:",
      reportId
    );


    const res =
      await API.analyzeWeeklyReport(
        Number(reportId)
      );


    console.log(
      "🤖 SUPERVISOR AI ANALYSIS RESPONSE:",
      res
    );


    if (!res?.success) {

      throw new Error(
        res?.message ||
        "Unable to analyse this report."
      );
    }


    showToast({

      title:
        res.already_analyzed
          ? "AI analysis already available"
          : "AI analysis completed",

      msg:
        res.already_analyzed
          ? "The existing analysis has been loaded."
          : "The report has been analysed successfully.",

      tone: "ai",

      icon: "sparkles"

    });


    /*
    |--------------------------------------------------------------------------
    | Reopen report with fresh AI data
    |--------------------------------------------------------------------------
    */

    closeModal();


    await openSupervisorReportReview(
      Number(reportId)
    );


  } catch (error) {

    console.error(
      "❌ Supervisor AI analysis error:",
      error
    );


    if (button) {

      button.disabled = false;

      button.innerHTML = `
        <i
          data-lucide="sparkles"
          style="width:14px;height:14px;"
        ></i>
        Analyze with AI
      `;

      icons();
    }


    showToast({

      title:
        "AI analysis failed",

      msg:
        error.message ||
        "Unable to analyse the report.",

      tone:
        "danger",

      icon:
        "alert-circle"

    });
  }
}


async function submitRegistration() {

  const fullName =
    document
      .getElementById("regFullName")
      ?.value.trim() || "";

  const matricNo =
    document
      .getElementById("regMatricNo")
      ?.value.trim() || "";

  const department =
    document
      .getElementById("regDepartment")
      ?.value.trim() || "";

  const programme =
    document
      .getElementById("regProgramme")
      ?.value.trim() || "";

  const level =
    document
      .getElementById("regLevel")
      ?.value.trim() || "";

  const email =
    document
      .getElementById("regEmail")
      ?.value.trim() || "";

  const password =
    document
      .getElementById("regPassword")
      ?.value || "";

  const phone =
    document
      .getElementById("regPhone")
      ?.value.trim() || "";


  if (
    !fullName ||
    !matricNo ||
    !department ||
    !programme ||
    !level ||
    !email ||
    !password
  ) {

    showToast({
      title: "Incomplete registration",
      msg:
        "Please fill in all required fields.",
      tone: "danger",
      icon: "alert-circle"
    });

    return;
  }


  if (password.length < 6) {

    showToast({
      title: "Password too short",
      msg:
        "Password must be at least 6 characters.",
      tone: "danger",
      icon: "lock"
    });

    return;
  }


  const btn =
    document.getElementById(
      "registerSubmitBtn"
    );


  try {

    if (btn) {

      btn.disabled = true;

      btn.innerHTML =
        '<span class="spinner"></span> Creating...';
    }


    const payload = {

      email,

      password,

      matric_no:
        matricNo,

      full_name:
        fullName,

      department,

      programme,

      level,

      phone:
        phone || null

    };


    console.log(
      "🧑‍🎓 REGISTRATION PAYLOAD:",
      payload
    );


    const res =
      await API.registerStudent(
        payload
      );


    console.log(
      "🧑‍🎓 REGISTRATION RESPONSE:",
      res
    );


    if (!res?.success) {

      throw new Error(
        res?.message ||
        "Unable to create account."
      );
    }


    closeModal();


    showToast({
      title: "Account created",
      msg:
        "Your student account has been created successfully. You can now sign in.",
      tone: "success",
      icon: "user-plus"
    });


    /*
    |--------------------------------------------------------------------------
    | PREFILL LOGIN
    |--------------------------------------------------------------------------
    */

    const loginEmail =
      document.getElementById(
        "loginEmail"
      );

    const loginPassword =
      document.getElementById(
        "loginPassword"
      );

    if (loginEmail) {
      loginEmail.value = email;
    }

    if (loginPassword) {
      loginPassword.value = password;
    }


  } catch (error) {

    console.error(
      "❌ Registration error:",
      error
    );

    showToast({
      title: "Registration failed",
      msg:
        error.message ||
        "Unable to create account.",
      tone: "danger",
      icon: "alert-circle"
    });

  } finally {

    if (btn) {

      btn.disabled = false;

      btn.innerHTML =
        "Create account";
    }
  }
}

/* =====================================================================
   NOTIFICATIONS PANEL (shared)
   ===================================================================== */
async function openNotifPanel(role) {

  openModal(`
    <div class="modal-head">

      <h3 class="card-title">
        Notifications
      </h3>

      <button
        class="icon-btn"
        onclick="closeModal()"
      >
        <i data-lucide="x"></i>
      </button>

    </div>

    <div
      class="modal-body"
      id="notificationModalBody"
      style="padding:10px 12px;"
    >

      <div class="state-block">

        <div class="state-icon">
          <span class="spinner"></span>
        </div>

        <h4>
          Loading notifications...
        </h4>

      </div>

    </div>
  `);

  icons();


  const body =
    document.getElementById(
      "notificationModalBody"
    );


  try {

    const res =
      await API.getNotifications();


    console.log(
      "🔔 NOTIFICATIONS:",
      res
    );


    if (!res?.success) {

      throw new Error(
        res?.message ||
        "Unable to load notifications."
      );
    }


    const notifications =
      Array.isArray(res.notifications)
        ? res.notifications
        : [];


    if (!notifications.length) {

      body.innerHTML = `

        <div class="state-block">

          <div class="state-icon">
            <i data-lucide="bell-off"></i>
          </div>

          <h4>
            No notifications
          </h4>

          <p>
            You're all caught up.
          </p>

        </div>

      `;

      icons();

      return;
    }


    body.innerHTML = notifications
      .map(notification => {

        const unread =
          Number(notification.is_read) === 0;


        return `

          <div
            style="
              display:flex;
              gap:12px;
              padding:12px 10px;
              border-radius:10px;
              background:${unread
                ? "var(--surface-2)"
                : "transparent"};
              cursor:pointer;
            "
            onclick="
              markNotificationAsRead(
                ${Number(notification.id)}
              )
            "
          >

            <div
              style="
                width:34px;
                height:34px;
                border-radius:10px;
                background:var(--surface-2);
                display:flex;
                align-items:center;
                justify-content:center;
                color:var(--primary);
                flex-shrink:0;
              "
            >

              <i
                data-lucide="bell"
                style="
                  width:16px;
                  height:16px;
                "
              ></i>

            </div>


            <div style="flex:1;">

              <div
                style="
                  font-size:13.5px;
                  font-weight:
                    ${unread ? "700" : "600"};
                "
              >
                ${escapeHtml(
                  notification.title
                )}
              </div>

              <div
                style="
                  font-size:12.5px;
                  color:var(--ink-soft);
                  margin-top:2px;
                "
              >
                ${escapeHtml(
                  notification.message
                )}
              </div>

              <div
                style="
                  font-size:11px;
                  color:var(--ink-faint);
                  margin-top:4px;
                "
              >
                ${formatDate(
                  notification.created_at
                )}
              </div>

            </div>


            ${
              unread
                ? `
                  <div
                    style="
                      width:7px;
                      height:7px;
                      border-radius:50%;
                      background:var(--primary);
                      margin-top:6px;
                      flex-shrink:0;
                    "
                  ></div>
                `
                : ""
            }

          </div>

        `;

      })
      .join("");


    icons();


  } catch (error) {

    console.error(
      "❌ Notification loading error:",
      error
    );


    body.innerHTML = `

      <div class="state-block">

        <div class="state-icon">
          <i data-lucide="alert-circle"></i>
        </div>

        <h4>
          Unable to load notifications
        </h4>

        <p>
          ${escapeHtml(
            error.message
          )}
        </p>

      </div>

    `;

    icons();
  }

}

async function markNotificationAsRead(
  notificationId
) {

  try {

    const res =
      await API.markNotificationRead(
        Number(notificationId)
      );


    if (!res?.success) {
      throw new Error(
        res?.message ||
        "Unable to mark notification as read."
      );
    }


    await updateNotificationBadge();


    /*
    |----------------------------------------------------------------------
    | Refresh panel
    |----------------------------------------------------------------------
    */

    await openNotifPanel(
      state.currentRole
    );


  } catch (error) {

    console.error(
      "❌ Mark notification read error:",
      error
    );

  }
}

async function updateNotificationBadge() {

  try {

    const res =
      await API.getUnreadNotificationCount();


    if (!res?.success) {
      return;
    }


    const count =
      Number(
        res.unread_count || 0
      );


    /*
    |--------------------------------------------------------------------------
    | Update both portal badges if they exist
    |--------------------------------------------------------------------------
    */

    document
      .querySelectorAll(
        "[data-notification-badge]"
      )
      .forEach(
        badge => {

          badge.textContent =
            count > 99
              ? "99+"
              : String(count);

          badge.style.display =
            count > 0
              ? ""
              : "none";

        }
      );


    /*
    |--------------------------------------------------------------------------
    | Fallback for common notification count IDs
    |--------------------------------------------------------------------------
    */

    [
      "studentNotificationBadge",
      "supervisorNotificationBadge"
    ].forEach(
      id => {

        const badge =
          document.getElementById(id);

        if (!badge) return;

        badge.textContent =
          count > 99
            ? "99+"
            : String(count);

        badge.style.display =
          count > 0
            ? ""
            : "none";

      }
    );


  } catch (error) {

    console.error(
      "❌ Notification badge error:",
      error
    );
  }
}

/* =====================================================================
   STUDENT PORTAL — NAVIGATION
   ===================================================================== */
const studentTitles = {
  dashboard:'Dashboard',
  profile:'My Profile',
  apply:'Apply for Industrial Training',
  recommendation:'AI Placement Recommendation',
  placement:'My Placement',
  logbook:'Training Logbook',
  reports:'Weekly Reports',
  supervisor:'My Supervisor',
  'ai-insights':'AI Insights',
  evaluation:'Final Evaluation',
  notifications:'Notifications',
  settings:'Settings'
};
function studentNav(sec){
  document.querySelectorAll('#studentSidebar .nav-item').forEach(el=>el.classList.toggle('active', el.dataset.sec===sec));
  document.getElementById('studentTitle').textContent = studentTitles[sec] || 'Dashboard';
  document.getElementById('studentBreadcrumb').textContent = 'Student Portal';
  toggleSidebar('student', false);
const map = {
  dashboard: renderStudentDashboard,
  profile: renderStudentProfile,
  apply: renderStudentApply,
  recommendation: renderStudentRecommendation,
  placement: renderStudentPlacement,
  logbook: renderStudentLogbook,
  reports: renderStudentReports,
  supervisor: renderStudentSupervisorPage,
  'ai-insights': renderStudentAIInsights,
  evaluation: renderStudentEvaluation,
  notifications: renderStudentNotifications,
  settings: renderStudentSettings
};
  (map[sec] || renderStudentDashboard)();
  document.getElementById('studentContent').scrollTop = 0;
  window.scrollTo(0,0);
}

function weekPill(n, current, cls, onClick){
  return `<div class="week-pill ${cls}" ${onClick?`onclick="${onClick}"`:''}>
    <div class="wk-num">W${n}</div>
    <div class="wk-state">${cls==='completed'?'Done':cls==='current'?'Current':'Pending'}</div>
  </div>`;
}
function weekTrackerHTML(current, total, onClickPrefix){
  let html = '';
  for(let i=1;i<=total;i++){
    const cls = i<current?'completed':(i===current?'current':'pending');
    html += weekPill(i, current, cls, onClickPrefix?`${onClickPrefix}(${i})`:null);
  }
  return `<div class="week-tracker">${html}</div>`;
  
}
function escapeHtml(value) {

  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}
/* ---------------------------------------------------------------------
   DASHBOARD
   --------------------------------------------------------------------- */
async function renderStudentDashboard() {

  const el = document.getElementById("studentContent");

  if (!el) {
    console.error("studentContent not found");
    return;
  }

  el.innerHTML = `
    <div style="padding:40px;text-align:center;">
      <div class="spinner"></div>
      <p style="color:var(--ink-soft);margin-top:12px;">
        Loading your dashboard...
      </p>
    </div>
  `;

  try {

    console.log("📊 Loading student dashboard...");

    const [dashboardRes, placementRes, reportsRes] =
      await Promise.all([
        API.getStudentDashboard(),
        API.getPlacement(),
        API.getWeeklyReports()
      ]);

    console.log("📊 Dashboard:", dashboardRes);
    console.log("📍 Placement:", placementRes);
    console.log("📚 Reports:", reportsRes);

    /*
    |--------------------------------------------------------------------------
    | STUDENT
    |--------------------------------------------------------------------------
    */

    const student =
      dashboardRes?.student ||
      state.currentStudent ||
      {};

    /*
    |--------------------------------------------------------------------------
    | PLACEMENT
    |--------------------------------------------------------------------------
    */

    const placement =
      placementRes?.placement ||
      {};

    /*
    |--------------------------------------------------------------------------
    | REPORTS
    |--------------------------------------------------------------------------
    */

    const reports =
      reportsRes?.reports ||
      [];

    /*
    |--------------------------------------------------------------------------
    | CALCULATE REPORT STATS
    |--------------------------------------------------------------------------
    */

    const totalReports = reports.length;

    const approvedReports = reports.filter(
      r => String(r.status).toLowerCase() === "approved"
    ).length;

    const pendingReports = reports.filter(
      r => String(r.status).toLowerCase() === "pending"
    ).length;

    /*
    |--------------------------------------------------------------------------
    | CURRENT WEEK
    |--------------------------------------------------------------------------
    */

    let currentWeek = 0;

    if (reports.length > 0) {

      currentWeek = Math.max(
        ...reports.map(r =>
          Number(r.week_number) || 0
        )
      );
    }

    /*
    |--------------------------------------------------------------------------
    | PERFORMANCE
    |--------------------------------------------------------------------------
    */

    const scores = reports
      .map(r => Number(r.ai_performance_score))
      .filter(score => !isNaN(score));

    const performanceScore =
      scores.length > 0
        ? Math.round(
            scores.reduce((a, b) => a + b, 0) /
            scores.length
          )
        : 0;

    /*
    |--------------------------------------------------------------------------
    | STUDENT NAME
    |--------------------------------------------------------------------------
    */

    const fullName =
      student.full_name ||
      student.name ||
      state.currentUser?.full_name ||
      "Student";

    const firstName =
      fullName.split(" ")[0];

    /*
    |--------------------------------------------------------------------------
    | PLACEMENT STATUS
    |--------------------------------------------------------------------------
    */

    const placementStatus =
      placement.status || "Not assigned";

    /*
    |--------------------------------------------------------------------------
    | ORGANIZATION
    |--------------------------------------------------------------------------
    */

    const organization =
      placement.organization_name ||
      "No organization assigned";

    /*
    |--------------------------------------------------------------------------
    | SUPERVISOR
    |--------------------------------------------------------------------------
    */

    const supervisor =
      placement.supervisor_name ||
      "Not assigned";

    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

/* Keep all your existing API calls/calculations above this point */

    el.innerHTML = `

      <div class="page-head">

        <div>

          <div class="eyebrow">
            STUDENT DASHBOARD
          </div>

          <h1>
            Good day, ${escapeHtml(firstName)} 👋
          </h1>

          <p class="page-sub">
            Here's an overview of your industrial training progress.
          </p>

        </div>

      </div>


      <div
        class="stats-grid"
        style="margin-bottom:20px;"
      >

        <div class="stat-card">
          <div class="stat-icon">
            <i data-lucide="calendar-days"></i>
          </div>

          <div>
            <div class="stat-label">
              CURRENT WEEK
            </div>

            <div class="stat-value">
              ${currentWeek || "—"}
              <span style="
                font-size:14px;
                font-weight:500;
              ">
                / 10
              </span>
            </div>
          </div>
        </div>


        <div class="stat-card">
          <div class="stat-icon">
            <i data-lucide="book-open"></i>
          </div>

          <div>
            <div class="stat-label">
              REPORTS SUBMITTED
            </div>

            <div class="stat-value">
              ${totalReports}
            </div>
          </div>
        </div>


        <div class="stat-card">
          <div class="stat-icon">
            <i data-lucide="check-circle-2"></i>
          </div>

          <div>
            <div class="stat-label">
              APPROVED REPORTS
            </div>

            <div class="stat-value">
              ${approvedReports}
            </div>
          </div>
        </div>


        <div class="stat-card">
          <div class="stat-icon">
            <i data-lucide="brain"></i>
          </div>

          <div>
            <div class="stat-label">
              AI PERFORMANCE
            </div>

            <div class="stat-value">
              ${
                performanceScore
                  ? performanceScore + "%"
                  : "—"
              }
            </div>
          </div>
        </div>

      </div>


      <div
        class="card"
        style="margin-bottom:18px;"
      >

        <div class="card-header">

          <div>

            <h3 class="card-title">
              Current Placement
            </h3>

            <p class="cell-sub">
              Your industrial training placement details.
            </p>

          </div>

          <span class="badge badge-success">
            ${escapeHtml(
              placementStatus
            )}
          </span>

        </div>


        <div class="card-pad">

          <div
            class="grid grid-4"
          >

            <div>
              <div class="stat-label">
                ORGANIZATION
              </div>

              <strong>
                ${escapeHtml(
                  organization
                )}
              </strong>
            </div>


            <div>
              <div class="stat-label">
                INDUSTRY
              </div>

              <strong>
                ${escapeHtml(
                  placement.industry || "—"
                )}
              </strong>
            </div>


            <div>
              <div class="stat-label">
                LOCATION
              </div>

              <strong>
                ${escapeHtml(
                  placement.location || "—"
                )}
              </strong>
            </div>


            <div>
              <div class="stat-label">
                SUPERVISOR
              </div>

              <strong>
                ${escapeHtml(
                  supervisor
                )}
              </strong>
            </div>

          </div>

        </div>

      </div>


      <div class="card">

        <div class="card-header">

          <div>

            <h3 class="card-title">
              Weekly Report Progress
            </h3>

            <p class="cell-sub">
              Keep your industrial training reports up to date.
            </p>

          </div>

          <button
            class="btn btn-primary btn-sm"
            onclick="
              studentNav('reports')
            "
          >
            <i
              data-lucide="plus"
              style="
                width:14px;
                height:14px;
              "
            ></i>

            Submit Report
          </button>

        </div>


        <div class="card-pad">

          <div
            style="
              display:flex;
              justify-content:space-between;
              margin-bottom:8px;
              font-size:13px;
            "
          >

            <span>
              Training progress
            </span>

            <strong>
              ${Math.min(
                currentWeek * 10,
                100
              )}%
            </strong>

          </div>


          <div
            style="
              height:8px;
              background:var(--surface-2);
              border-radius:20px;
              overflow:hidden;
            "
          >

            <div
              style="
                width:${Math.min(
                  currentWeek * 10,
                  100
                )}%;
                height:100%;
                background:var(--primary);
                border-radius:20px;
              "
            ></div>

          </div>


          <div
            style="
              display:flex;
              gap:24px;
              margin-top:18px;
              flex-wrap:wrap;
              font-size:13px;
            "
          >

            <span>
              <strong>${totalReports}</strong>
              Submitted
            </span>

            <span>
              <strong>${approvedReports}</strong>
              Approved
            </span>

            <span>
              <strong>${pendingReports}</strong>
              Pending
            </span>

          </div>

        </div>

      </div>

    `;

    /*
    |--------------------------------------------------------------------------
    | REINITIALIZE ICONS
    |--------------------------------------------------------------------------
    */

    if (typeof lucide !== "undefined") {
      lucide.createIcons();
    }

  } catch (error) {

    console.error(
      "❌ Student dashboard error:",
      error
    );

    el.innerHTML = `

      <div class="card">

        <div style="
          text-align:center;
          padding:40px;
        ">

          <i
            data-lucide="alert-circle"
            style="width:40px;height:40px;"
          ></i>

          <h3>
            Unable to load dashboard
          </h3>

          <p style="color:var(--ink-soft);">
            ${escapeHtml(
              error.message ||
              "Something went wrong while loading your dashboard."
            )}
          </p>

          <button
            class="btn btn-primary"
            onclick="studentNav('dashboard')"
          >
            Try Again
          </button>

        </div>

      </div>

    `;

    if (typeof lucide !== "undefined") {
      lucide.createIcons();
    }
  }
}
function statCard(label,value,icon,color,bg,trendText,trendDir){
  return `<div class="card stat-card">
    <div class="stat-top"><div class="stat-icon" style="background:${bg};color:${color};"><i data-lucide="${icon}"></i></div>
    ${trendDir?`<span class="stat-trend ${trendDir==='up'?'trend-up':'trend-down'}"><i data-lucide="${trendDir==='up'?'arrow-up-right':'arrow-down-right'}" style="width:12px;height:12px;"></i>${trendText}</span>`:''}</div>
    <div class="stat-value">${value}</div><div class="stat-label">${label}</div>
    ${!trendDir?`<div class="stat-trend" style="color:var(--ink-faint);">${trendText}</div>`:''}
  </div>`;
}
function activityRow(icon,title,sub,time,tone){
  const c = {info:'var(--info)',success:'var(--success)',ai:'var(--ai-1)',warning:'var(--warning)'}[tone];
  const bg = {info:'var(--info-tint)',success:'var(--success-tint)',ai:'var(--ai-tint)',warning:'var(--warning-tint)'}[tone];
  return `<div style="display:flex;gap:12px;">
    <div style="width:32px;height:32px;border-radius:9px;background:${bg};color:${c};display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i data-lucide="${icon}" style="width:15px;height:15px;"></i></div>
    <div style="flex:1;"><div style="font-size:13.5px;font-weight:600;">${title}</div><div style="font-size:12.5px;color:var(--ink-soft);">${sub}</div></div>
    <div style="font-size:11.5px;color:var(--ink-faint);white-space:nowrap;">${time}</div>
  </div>`;
}

/* ---------------------------------------------------------------------
   PROFILE
   --------------------------------------------------------------------- */
async function renderStudentProfile() {

  const content =
    document.getElementById("studentContent");

  content.innerHTML = `
    <div class="card">
      <div class="state-block">
        <div class="spinner"></div>
        <h4>Loading profile...</h4>
      </div>
    </div>
  `;

  try {

    const res =
      await API.getStudentProfile();

    if (!res?.success) {
      throw new Error(
        res?.message ||
        "Unable to load profile."
      );
    }

    const p =
      res.student ||
      res.profile ||
      res.data ||
      res;

    content.innerHTML = `

      <div class="card">

        <div class="card-header">
          <h3 class="card-title">
            My Profile
          </h3>
        </div>

        <div class="card-pad">

          ${profField("Full Name", p.full_name)}
          ${profField("Matric No.", p.matric_no)}
          ${profField("Department", p.department)}
          ${profField("Programme", p.programme)}
          ${profField("Level", p.level)}
          ${profField("Phone", p.phone || "—")}
          ${profField("Skills", p.skills || "—")}
          ${profField(
            "Career Interest",
            p.career_interest || "—"
          )}
          ${profField(
            "Industry Interest",
            p.industry_interest || "—"
          )}
          ${profField(
            "Location Preference",
            p.location_preference || "—"
          )}

        </div>

      </div>
    `;

    icons();

  } catch (error) {

    content.innerHTML = `
      <div class="card">
        <div class="state-block">
          <h4>Unable to load profile</h4>
          <p>${escapeHtml(error.message)}</p>
        </div>
      </div>
    `;

    icons();
  }
}


function openEditProfileModal() {

  const s =
    state.currentStudent ||
    {};

  const phone =
    s.phone || "";

  const preferredLocation =
    s.preferred_location ||
    s.preferredLocation ||
    "";

  const experience =
    s.previous_experience ||
    s.previousExperience ||
    "";

  openModal(`
    <div class="modal-head">
      <h3 class="card-title">Edit profile</h3>

      <button
        class="icon-btn"
        onclick="closeModal()"
      >
        <i data-lucide="x"></i>
      </button>
    </div>

    <div class="modal-body">

      <div class="field">
        <label>Phone</label>

        <input
          class="input"
          id="editPhone"
          value="${escapeHtml(phone)}"
        >
      </div>

      <div class="field">
        <label>Preferred location</label>

        <input
          class="input"
          id="editPreferredLocation"
          value="${escapeHtml(preferredLocation)}"
        >
      </div>

      <div class="field">
        <label>Previous experience</label>

        <textarea
          class="input"
          id="editPreviousExperience"
          rows="5"
        >${escapeHtml(experience)}</textarea>
      </div>

    </div>

    <div class="modal-foot">

      <button
        class="btn btn-outline"
        onclick="closeModal()"
      >
        Cancel
      </button>

      <button
        class="btn btn-primary"
        onclick="saveStudentProfile()"
      >
        Save changes
      </button>

    </div>
  `);

  icons();
}


async function saveStudentProfile() {

  const phone =
    document.getElementById("editPhone")?.value.trim() || "";

  const preferredLocation =
    document.getElementById("editPreferredLocation")?.value.trim() || "";

  const previousExperience =
    document.getElementById("editPreviousExperience")?.value.trim() || "";

  try {

    const res = await API.updateStudentProfile({
      phone,
      preferred_location: preferredLocation,
      previous_experience: previousExperience
    });

    console.log("✏️ PROFILE UPDATE:", res);

    if (!res.success) {
      throw new Error(
        res.message || "Unable to update profile."
      );
    }

    closeModal();

    showToast({
      title: "Profile updated",
      msg: "Your profile has been updated successfully.",
      tone: "success",
      icon: "check-circle-2"
    });

    await renderStudentProfile();

  } catch (error) {

    console.error("❌ Profile update error:", error);

    showToast({
      title: "Update failed",
      msg: error.message,
      tone: "danger",
      icon: "alert-circle"
    });
  }
}
function profField(label,val){ return `<div class="field"><label>${label}</label><div style="font-size:13.5px;padding:9px 0;">${val}</div></div>`; }
function openEditProfileModal(){
  openModal(`<div class="modal-head"><h3 class="card-title">Edit profile</h3><button class="icon-btn" onclick="closeModal()"><i data-lucide="x"></i></button></div>
  <div class="modal-body">
    <div class="grid grid-2">
      <div class="field"><label>Phone</label><input class="input" value="${DB.student.phone}"></div>
      <div class="field"><label>Preferred location</label><input class="input" value="${DB.student.preferredLocation}"></div>
    </div>
    <div class="field"><label>Previous experience</label><textarea class="input">${DB.student.previousExperience}</textarea></div>
  </div>
  <div class="modal-foot"><button class="btn btn-outline" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="closeModal();showToast({title:'Profile updated', tone:'success', icon:'check-circle-2'})">Save changes</button></div>`);
}

/* ---------------------------------------------------------------------
   IT APPLICATION (multi-step)
   --------------------------------------------------------------------- */
function renderStudentApply(){
  if(!state.applicationData.skills.length) state.applicationData = {skills:['Programming','Web Development','Database Management'], careers:['Backend Development','Software Engineering']};
  renderApplyStep();
}
function stepperHTML(step){
  const labels = ['Academic Info','Skills','Career Interest','Industry Preferences','Review'];
  return `<div class="stepper">${labels.map((l,i)=>{
    const n=i+1; const cls = n<step?'done':(n===step?'active':'');
    return `<div class="step-item ${cls}"><div class="step-circle">${n<step?'<i data-lucide=\"check\" style=\"width:14px;height:14px;\"></i>':n}</div><div class="step-label">${l}</div></div>${n<5?`<div class="step-line ${n<step?'done':''}"></div>`:''}`;
  }).join('')}</div>`;
}
function renderApplyStep(){
  const step = state.applicationStep;
  let body = '';
  if(step===1){
    body = `<div class="grid grid-2">
      <div class="field"><label>Department</label><select class="input" id="apDept">${DB.departments.map(d=>`<option ${d===DB.student.department?'selected':''}>${d}</option>`).join('')}</select></div>
      <div class="field"><label>Programme</label><select class="input" id="apProg">${DB.programmes.map(d=>`<option ${d===DB.student.programme?'selected':''}>${d}</option>`).join('')}</select></div>
      <div class="field"><label>Level</label><select class="input"><option ${DB.student.level==='300 Level'?'selected':''}>300 Level</option><option>400 Level</option><option>200 Level</option></select></div>
      <div class="field"><label>Institution</label><input class="input" value="Federal University of Technology, Akure"></div>
      <div class="field col-span-2"><label>Expected training duration</label><select class="input" id="apDuration"><option value="10" selected>10 weeks (SIWES standard)</option><option value="24">6 months</option><option value="52">1 year</option></select></div></div>`;
  } else if(step===2){
    body = `<p class="cell-sub">Select the skills that best describe you — the AI uses this to match organizations.</p>
    <div class="chip-select" id="skillChips">${DB.skillsCatalog.map(sk=>`<div class="chip ${state.applicationData.skills.includes(sk)?'selected':''}" onclick="toggleChip('skills','${sk}')">${sk}</div>`).join('')}</div>`;
  } else if(step===3){
    body = `<p class="cell-sub">What career path are you aiming for after graduation?</p>
    <div class="chip-select" id="careerChips">${DB.careerCatalog.map(sk=>`<div class="chip ${state.applicationData.careers.includes(sk)?'selected':''}" onclick="toggleChip('careers','${sk}')">${sk}</div>`).join('')}</div>`;
  } else if(step===4){
    body = `<div class="grid grid-2">
      <div class="field"><label>Preferred industry</label><select class="input" id="apIndustry"><option selected>Software Development</option><option>Data Services</option><option>Finance</option><option>Renewable Energy</option><option>Media &amp; Broadcasting</option></select></div>
      <div class="field"><label>Preferred location</label><input class="input" id="apLocation" value="${DB.student.preferredLocation}"></div>
      <div class="field"><label>Remote / onsite</label><select class="input"><option selected>Onsite</option><option>Remote</option><option>Hybrid</option></select></div>
      <div class="field"><label>Organization size</label><select class="input"><option>Startup (1-20 staff)</option><option selected>Mid-size (20-200 staff)</option><option>Large enterprise (200+)</option></select></div>
    </div>`;
  } else if(step===5){
    body = `<p class="cell-sub" style="margin-bottom:16px;">Review everything below, then let AI find your placement.</p>
    <div class="grid grid-2" style="margin-bottom:8px;">
      ${profField('Department', DB.student.department)} ${profField('Programme', DB.student.programme)}
      ${profField('Preferred industry','Software Development')} ${profField('Preferred location', DB.student.preferredLocation)}
    </div>
    <div class="field"><label>Skills</label><div class="chip-select">${state.applicationData.skills.map(s=>`<span class="skill-tag">${s}</span>`).join('')}</div></div>
    <div class="field"><label>Career interests</label><div class="chip-select">${state.applicationData.careers.map(s=>`<span class="skill-tag" style="background:var(--ai-tint);color:var(--ai-1);">${s}</span>`).join('')}</div></div>`;
  }
  document.getElementById('studentContent').innerHTML = `
    <div class="card card-pad">
      ${stepperHTML(step)}
      <div style="min-height:220px;">${body}</div>
      <div style="display:flex;justify-content:space-between;margin-top:24px;border-top:1px solid var(--border-soft);padding-top:18px;">
        <button class="btn btn-outline" ${step===1?'disabled':''} onclick="changeApplyStep(-1)"><i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back</button>
        ${step<5 ? `<button class="btn btn-primary" onclick="changeApplyStep(1)">Continue <i data-lucide="arrow-right" style="width:14px;height:14px;"></i></button>`
                  : `<button class="btn btn-ai" onclick="startApplicationAndAnalyze()"><i data-lucide="sparkles" style="width:15px;height:15px;"></i> Analyze My Placement with AI</button>`}
      </div>
    </div>`;
  icons();
}
function toggleChip(kind, val){
  const arr = state.applicationData[kind];
  const idx = arr.indexOf(val);
  if(idx>-1) arr.splice(idx,1); else arr.push(val);
  renderApplyStep();
}
function changeApplyStep(dir){
  state.applicationStep = Math.min(5, Math.max(1, state.applicationStep+dir));
  renderApplyStep();
}

async function startApplicationAndAnalyze() {

  try {

    const trainingDuration =
      document.getElementById("apDuration")?.value || 10;

    const preferredLocation =
      document.getElementById("apLocation")?.value.trim() || "";

    const payload = {
      training_duration: Number(trainingDuration),
      preferred_location: preferredLocation
    };

    console.log(
      "📨 APPLICATION PAYLOAD:",
      payload
    );

    const res =
      await API.submitApplication(payload);

    console.log(
      "📨 APPLICATION RESPONSE:",
      res
    );

    if (!res || !res.success) {

      throw new Error(
        res?.message ||
        "Unable to submit application."
      );
    }

    const applicationId =
      res.application_id ||
      res.id ||
      null;

    if (!applicationId) {

      throw new Error(
        "Application was created but no application ID was returned."
      );
    }

    state.applicationData = {
      ...state.applicationData,
      application_id: Number(applicationId)
    };

    localStorage.setItem(
      "AI-ITMS_application_id",
      String(applicationId)
    );

    console.log(
      "✅ APPLICATION ID:",
      applicationId
    );

    showToast({
      title: "Application submitted",
      msg:
        "Your application has been submitted successfully.",
      tone: "success",
      icon: "check-circle-2"
    });

    studentNav("recommendation");

  } catch (error) {

    console.error(
      "❌ Application error:",
      error
    );

    showToast({
      title: "Application failed",
      msg: error.message,
      tone: "danger",
      icon: "alert-circle"
    });
  }
}


/* ---------------------------------------------------------------------
   AI PLACEMENT RECOMMENDATION
   --------------------------------------------------------------------- */
async function renderStudentRecommendation() {

  document.getElementById(
    "studentContent"
  ).innerHTML = `
    <div class="card">

      <div class="card-pad ai-processing">

        <div class="ai-orb">
          <i data-lucide="sparkles"></i>
        </div>

        <h3>Analyzing your placement</h3>

        <div
          class="step-line-text"
          id="aiStepText"
        >
          Analyzing your academic background...
        </div>

        <div
          class="progress-track"
          style="max-width:320px;margin:18px auto 0;"
        >
          <div
            class="progress-fill ai"
            id="aiProgressFill"
            style="width:8%;"
          ></div>
        </div>

      </div>

    </div>
  `;

  icons();

  await runAIPlacementAnalysis();
}

async function runAIPlacementAnalysis() {

  const steps = [
    "Analyzing your academic background...",
    "Matching your skills...",
    "Analyzing career interests...",
    "Finding suitable organizations...",
    "Calculating placement compatibility..."
  ];

  const textEl =
    document.getElementById("aiStepText");

  const fillEl =
    document.getElementById("aiProgressFill");

  if (!textEl) {
    console.error(
      "AI progress text element not found."
    );
    return;
  }

  let i = 0;

  const interval = setInterval(() => {

    i++;

    if (i < steps.length) {

      textEl.textContent =
        steps[i];

      if (fillEl) {

        fillEl.style.width =
          `${((i + 1) / steps.length) * 92}%`;
      }
    }

  }, 620);


  try {

    console.log(
      "🤖 Starting AI placement analysis..."
    );

    /*
    |--------------------------------------------------------------------------
    | IMPORTANT
    |--------------------------------------------------------------------------
    | The backend determines the application from the
    | currently logged-in student's PHP session.
    |
    | DO NOT SEND application_id HERE.
    |--------------------------------------------------------------------------
    */

    const res =
      await API.getAIPlacementRecommendation();

    console.log(
      "🤖 AI PLACEMENT RESPONSE:",
      res
    );

    clearInterval(interval);


    if (!res || !res.success) {

      throw new Error(
        res?.message ||
        "AI placement analysis failed."
      );
    }


    const recommendation =
      res.recommendation ||
      res.analysis ||
      res.data ||
      null;


    if (!recommendation) {

      throw new Error(
        "AI returned no placement recommendation."
      );
    }


    console.log(
      "✅ AI RECOMMENDATION:",
      recommendation
    );


    renderRecommendationResult(
      recommendation
    );


    showToast({
      title: "AI analysis complete",
      msg:
        "Your placement recommendation is ready.",
      tone: "ai",
      icon: "sparkles"
    });


  } catch (error) {

    clearInterval(interval);

    console.error(
      "❌ AI placement error:",
      error
    );


    const content =
      document.getElementById(
        "studentContent"
      );


    if (content) {

      content.innerHTML = `

        <div class="card">

          <div class="card-pad state-block">

            <div class="state-icon">
              <i data-lucide="alert-circle"></i>
            </div>

            <h4>
              AI analysis failed
            </h4>

            <p>
              ${escapeHtml(
                error.message ||
                "Unable to generate placement recommendation."
              )}
            </p>

            <button
              class="btn btn-primary"
              onclick="runAIPlacementAnalysis()"
            >
              Try Again
            </button>

          </div>

        </div>

      `;

      icons();
    }
  }
}

function renderRecommendationResult(rec){
  state.aiRecommendation = rec;

  document.getElementById('studentContent').innerHTML = `
    <div class="grid" style="grid-template-columns:1.4fr 1fr;align-items:start;">
      <div class="card ai-card">
        <div class="card-header"><span class="ai-badge"><i data-lucide="sparkles"></i>AI Recommendation</span><span class="badge badge-success">Ready</span></div>
        <div class="card-pad">
          <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
            <div style="flex:1;min-width:220px;">
              <h2 style="margin:0 0 4px;">${rec.org}</h2>
              <p class="cell-sub" style="margin:0 0 10px;">${rec.industry} &middot; ${rec.location}</p>
              <span class="badge badge-primary">${rec.role}</span>
            </div>
            ${gaugeSVG(rec.match)}
          </div>
          <div class="card" style="background:var(--surface-2);border:none;margin-top:18px;padding:16px;">
            <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px;"><i data-lucide="lightbulb" style="width:15px;height:15px;color:var(--ai-1);"></i><strong style="font-size:13px;">Why AI recommended this organization</strong></div>
            <p style="font-size:13.5px;color:var(--ink-soft);margin:0;">${rec.reason}</p>
          </div>
          <h4 style="margin:20px 0 10px;">Matching factors</h4>
          ${rec.factors.map(f=>`<div class="progress-row"><span class="label">${f.label}</span><span class="val">${f.value}%</span></div><div class="progress-track" style="margin-bottom:12px;"><div class="progress-fill ai" style="width:${f.value}%;"></div></div>`).join('')}
          <div style="display:flex;gap:10px;margin-top:10px;flex-wrap:wrap;">
            <button class="btn btn-primary" onclick="acceptRecommendation()"><i data-lucide="check" style="width:14px;height:14px;"></i> Accept Recommendation</button>
            <button class="btn btn-outline" onclick="studentNav('placement');renderOrgDetails(DB.organizations[0])">View Organization</button>
            <button class="btn btn-ghost" onclick="runAIPlacementAnalysis()"><i data-lucide="refresh-cw" style="width:14px;height:14px;"></i> Request Another Recommendation</button>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3 class="card-title">Alternative recommendations</h3></div>
        <div class="card-pad" style="display:flex;flex-direction:column;gap:12px;">
          ${rec.alternatives.map(a=>`
            <div class="card" style="padding:14px;">
              <div style="display:flex;justify-content:space-between;align-items:start;">
                <div><strong style="font-size:13.5px;">${a.org}</strong><div class="cell-sub">${a.industry} &middot; ${a.location}</div></div>
                <span class="badge badge-ai">${a.match}%</span>
              </div>
              <div class="progress-track" style="margin-top:10px;margin-bottom:10px;"><div class="progress-fill ai" style="width:${a.match}%;"></div></div>
              <div style="display:flex;justify-content:space-between;align-items:center;">
                <span class="cell-sub">${a.role}</span>
                <button class="btn btn-outline btn-sm" onclick='viewAltOrg(${JSON.stringify(a.org)})'>View</button>
              </div>
            </div>`).join('')}
        </div>
      </div>
    </div>`;
  icons();
}
function gaugeSVG(pct){
  const r=52, c=2*Math.PI*r, off=c-(pct/100*c);
  return `<div class="gauge-wrap" style="width:130px;height:130px;flex-shrink:0;">
    <svg width="130" height="130" viewBox="0 0 130 130">
      <circle cx="65" cy="65" r="${r}" fill="none" stroke="var(--border)" stroke-width="11"/>
      <circle cx="65" cy="65" r="${r}" fill="none" stroke="url(#aiGrad)" stroke-width="11" stroke-linecap="round" stroke-dasharray="${c}" stroke-dashoffset="${off}"/>
      <defs><linearGradient id="aiGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="var(--ai-1)"/><stop offset="100%" stop-color="var(--ai-2)"/></linearGradient></defs>
    </svg>
    <div class="gauge-center"><div class="gnum">${pct}%</div><div class="glabel">Match</div></div>
  </div>`;
}
function viewAltOrg(name){
  const org = DB.organizations.find(o=>o.name===name) || DB.organizations[1];
  studentNav('placement'); renderOrgDetails(org);
}

async function acceptRecommendation() {

  try {

    const res =
      await API.acceptPlacement();

    console.log(
      "✅ ACCEPT PLACEMENT RESPONSE:",
      res
    );

    if (!res?.success) {
      throw new Error(
        res?.message ||
        "Unable to accept recommendation."
      );
    }

    showToast({
      title: "Placement request submitted",
      msg:
        "Your placement is now awaiting supervisor approval.",
      tone: "success",
      icon: "check-circle-2"
    });

    await renderStudentPlacement();

  } catch (error) {

    console.error(
      "❌ Accept placement error:",
      error
    );

    showToast({
      title: "Placement failed",
      msg: error.message,
      tone: "danger",
      icon: "alert-circle"
    });
  }
}

/* ---------------------------------------------------------------------
   PLACEMENT / ORGANIZATION DETAILS / CONFIRMATION
   --------------------------------------------------------------------- */
async function renderStudentPlacement() {

  const el =
    document.getElementById(
      "studentContent"
    );

  if (!el) return;

  el.innerHTML = `
    <div class="card">
      <div class="card-pad" style="text-align:center;padding:40px;">
        <div class="spinner"></div>
        <p class="cell-sub">
          Loading placement information...
        </p>
      </div>
    </div>
  `;

  try {

    const res =
      await API.getPlacement();

    console.log(
      "📍 STUDENT PLACEMENT:",
      res
    );

    if (!res.success) {

      if (res.message === "No placement found.") {

        el.innerHTML = `
          <div class="card">
            <div class="card-pad state-block">

              <div class="state-icon">
                <i data-lucide="building-2"></i>
              </div>

              <h4>No placement yet</h4>

              <p>
                Complete your application and review your AI recommendation.
              </p>

              <button
                class="btn btn-primary"
                onclick="studentNav('recommendation')"
              >
                View AI Recommendation
              </button>

            </div>
          </div>
        `;

        icons();
        return;
      }

      throw new Error(
        res.message ||
        "Unable to retrieve placement."
      );
    }

    const p =
      res.placement ||
      res.data ||
      {};

    const status =
      p.status || "pending";

    const statusClass =
      String(status).toLowerCase() === "active"
        ? "badge-success"
        : "badge-warning";

    const studentName =
      p.student_name ||
      state.currentStudent?.full_name ||
      "—";

    const organization =
      p.organization_name ||
      "—";

    const role =
      p.role ||
      p.position ||
      "—";

    const supervisor =
      p.supervisor_name ||
      "Not assigned";

    const industry =
      p.industry ||
      "—";

    const location =
      p.location ||
      "—";

    const startDate =
      p.start_date ||
      "—";

    const endDate =
      p.end_date ||
      "—";

    el.innerHTML = `

      <div class="card">

        <div class="card-header">

          <div>

            <h3 class="card-title">
              Placement confirmation
            </h3>

            <div class="cell-sub">
              Your industrial training placement
            </div>

          </div>

          <span class="badge ${statusClass}">
            <i data-lucide="check-circle-2"></i>
            ${escapeHtml(status)}
          </span>

        </div>

        <div class="card-pad">

          <div class="grid grid-3">

            ${profField(
              "Student",
              escapeHtml(studentName)
            )}

            ${profField(
              "Organization",
              escapeHtml(organization)
            )}

            ${profField(
              "Role",
              escapeHtml(role)
            )}

            ${profField(
              "Supervisor",
              escapeHtml(supervisor)
            )}

            ${profField(
              "Industry",
              escapeHtml(industry)
            )}

            ${profField(
              "Location",
              escapeHtml(location)
            )}

            ${profField(
              "Start date",
              escapeHtml(startDate)
            )}

            ${profField(
              "End date",
              escapeHtml(endDate)
            )}

            ${profField(
              "Status",
              escapeHtml(status)
            )}

          </div>

        </div>

      </div>

    `;

    icons();

  } catch (error) {

    console.error(
      "❌ Placement error:",
      error
    );

    el.innerHTML = `
      <div class="card">
        <div class="card-pad state-block">

          <div class="state-icon">
            <i data-lucide="alert-circle"></i>
          </div>

          <h4>Unable to load placement</h4>

          <p>
            ${escapeHtml(error.message)}
          </p>

        <button
          class="btn btn-primary"
          onclick="renderStudentRecommendation()"
        >
          Try Again
        </button>

        </div>
      </div>
    `;

    icons();
  }
}
function renderOrgDetails(org){
  const slot = document.getElementById('orgDetailsSlot');
  if(!slot) return;
  slot.innerHTML = `
    <div class="card">
      <div class="card-header">
        <div style="display:flex;align-items:center;gap:12px;">
          <div class="avatar" style="border-radius:12px;width:42px;height:42px;">${org.name[0]}</div>
          <div><h3 class="card-title">${org.name}</h3><span class="cell-sub">${org.industry} &middot; ${org.location}</span></div>
        </div>
        <span class="badge ${org.status==='Active'?'badge-success':'badge-neutral'}">${org.status}</span>
      </div>
      <div class="card-pad">
        <p style="font-size:13.5px;color:var(--ink-soft);">${org.desc}</p>
        <div class="grid grid-3" style="margin:16px 0;">
          ${profField('Supervisor', org.supervisor)} ${profField('Capacity', org.assigned+' / '+org.capacity+' students')} ${profField('Suitable courses', org.courses.join(', '))}
        </div>
        <div class="field"><label>Required skills</label><div class="chip-select">${org.skills.map(s=>`<span class="skill-tag">${s}</span>`).join('')}</div></div>
        <div class="field"><label>Available training roles</label><div class="chip-select">${org.roles.map(s=>`<span class="skill-tag" style="background:var(--accent-tint);color:var(--accent-600);">${s}</span>`).join('')}</div></div>
        <div class="card ai-card" style="padding:16px;margin-top:16px;">
          <span class="ai-badge"><i data-lucide="sparkles"></i>Why this organization fits you</span>
          <p style="font-size:13.5px;color:var(--ink-soft);margin:10px 0 0;">${org.name}'s stack and role requirements line up closely with ${DB.student.name.split(' ')[0]}'s coursework in ${DB.student.department} and stated interest in ${DB.student.careerInterests.join(' / ')}.</p>
        </div>
      </div>
    </div>`;
  icons();
}

/* ---------------------------------------------------------------------
   TRAINING LOGBOOK + WEEKLY REPORT SUBMISSION + AI ANALYSIS
   --------------------------------------------------------------------- */
async function renderStudentLogbook() {

  const el = document.getElementById("studentContent");

  if (!el) return;

  el.innerHTML = `
    <div class="card">
      <div class="card-pad state-block" style="padding:40px;">
        <div class="spinner"></div>
        <p>Loading training logbook...</p>
      </div>
    </div>
  `;

  try {

    const [placementRes, reportsRes] = await Promise.all([
      API.getPlacement(),
      API.getWeeklyReports()
    ]);

    if (!placementRes.success) {
      throw new Error(
        placementRes.message || "Unable to load placement."
      );
    }

    if (!reportsRes.success) {
      throw new Error(
        reportsRes.message || "Unable to load reports."
      );
    }

    const placement =
      placementRes.placement || {};

    const reports =
      reportsRes.reports || [];

    state.logbookReports = reports;

    const totalWeeks = 10;

    let currentWeek = 1;

    if (placement.start_date) {

      const start = new Date(placement.start_date);
      const today = new Date();

      const diff =
        Math.floor(
          (today - start) /
          (1000 * 60 * 60 * 24 * 7)
        );

      currentWeek = Math.min(
        totalWeeks,
        Math.max(1, diff + 1)
      );
    }

    state.logbookCurrentWeek = currentWeek;

    if (!state.selectedWeek) {
      state.selectedWeek = currentWeek;
    }

    el.innerHTML = `
      <div class="card" style="margin-bottom:18px;">

        <div class="card-header">

          <div>
            <h3 class="card-title">
              Training Logbook
            </h3>

            <div class="cell-sub">
              Week ${currentWeek} of ${totalWeeks}
            </div>
          </div>

          <span class="badge badge-primary">
            Week ${currentWeek}
          </span>

        </div>

        <div class="card-pad">

          <div class="week-tracker">

            ${Array.from(
              { length: totalWeeks },
              (_, index) => {

                const week = index + 1;

                const report =
                  reports.find(
                    r => Number(r.week_number) === week
                  );

                let cls = "pending";
                let label = "Locked";

                if (report) {
                  cls = "completed";
                  label =
                    String(report.status || "")
                      .toLowerCase() === "approved"
                      ? "Done"
                      : "Submitted";
                } else if (week === currentWeek) {
                  cls = "current";
                  label = "Current";
                } else if (week === currentWeek + 1) {
                  cls = "pending";
                  label = "Next";
                }

                if (week === Number(state.selectedWeek)) {
                  cls += " active-select";
                }

                return `
                  <div
                    class="week-pill ${cls}"
                    onclick="selectLogbookWeek(${week})"
                  >
                    <div class="wk-num">W${week}</div>
                    <div class="wk-state">${label}</div>
                  </div>
                `;
              }
            ).join("")}

          </div>

        </div>

      </div>

      <div id="logbookFormSlot"></div>
    `;

    await renderLogbookForm(
      Number(state.selectedWeek)
    );

    icons();

  } catch (error) {

    console.error(
      "❌ Logbook error:",
      error
    );

    el.innerHTML = `
      <div class="card">
        <div class="card-pad state-block">

          <div class="state-icon">
            <i data-lucide="alert-circle"></i>
          </div>

          <h4>Unable to load logbook</h4>

          <p>
            ${escapeHtml(error.message)}
          </p>

          <button
            class="btn btn-primary"
            onclick="studentNav('logbook')"
          >
            Try Again
          </button>

        </div>
      </div>
    `;

    icons();
  }
}


function selectLogbookWeek(week) {

  state.selectedWeek = Number(week);

  renderStudentLogbook();
}


async function renderLogbookForm(week) {

  const slot =
    document.getElementById(
      "logbookFormSlot"
    );

  if (!slot) return;

  const reports =
    state.logbookReports || [];

  const report =
    reports.find(
      r => Number(r.week_number) === Number(week)
    );

  const currentWeek =
    Number(state.logbookCurrentWeek || 1);

  if (!report && week > currentWeek) {

    slot.innerHTML = `
      <div class="card">

        <div class="card-pad state-block">

          <div class="state-icon">
            <i data-lucide="lock"></i>
          </div>

          <h4>
            Week ${week} is not open yet
          </h4>

          <p>
            You can submit the report when you reach Week ${week}.
          </p>

        </div>

      </div>
    `;

    icons();
    return;
  }

  const readOnly = !!report;

  const activities =
    report?.activities || "";

  const skills =
    report?.skills_learned || "";

  const challenges =
    report?.challenges || "";

  const studentComment =
    report?.student_comment || "";

  slot.innerHTML = `

    <div class="card">

      <div class="card-header">

        <div>
          <h3 class="card-title">
            Week ${week}
            ${readOnly ? "Report" : "— New Report"}
          </h3>

          ${
            readOnly
              ? statusBadgeForReport(
                  report.status
                )
              : `<span class="badge badge-neutral">
                   Draft
                 </span>`
          }

        </div>

      </div>

      <div class="card-pad">

        <div class="field">

          <label>
            Activities performed
          </label>

          <textarea
            class="input"
            id="rf_activities"
            rows="7"
            ${readOnly ? "disabled" : ""}
          >${escapeHtml(activities)}</textarea>

        </div>

        <div class="field">

          <label>
            Skills learned
          </label>

          <textarea
            class="input"
            id="rf_skills"
            rows="5"
            ${readOnly ? "disabled" : ""}
          >${escapeHtml(skills)}</textarea>

        </div>

        <div class="field">

          <label>
            Challenges encountered
          </label>

          <textarea
            class="input"
            id="rf_challenges"
            rows="5"
            ${readOnly ? "disabled" : ""}
          >${escapeHtml(challenges)}</textarea>

        </div>

        <div class="field">

          <label>
            Student comment
          </label>

          <textarea
            class="input"
            id="rf_comment"
            rows="4"
            ${readOnly ? "disabled" : ""}
          >${escapeHtml(studentComment)}</textarea>

        </div>

        ${
          !readOnly
            ? `
              <button
                class="btn btn-primary"
                onclick="submitWeekReport(${week})"
              >
                <i data-lucide="send"></i>
                Submit Report
              </button>
            `
            : ""
        }

      </div>

    </div>

    <div
      id="aiAnalysisSlot"
      style="margin-top:18px;"
    >

      ${
        report
          ? await renderExistingAIAnalysis(
              report
            )
          : ""
      }

    </div>

  `;

  icons();
}

async function renderExistingAIAnalysis(report) {

  if (!report?.ai_analysis_id) {
    return awaitingAnalysisBlock();
  }

  try {

    const res =
      await API.getReportAnalysis(
        report.id
      );

    if (!res.success) {
      return awaitingAnalysisBlock();
    }

    const analysis =
      res.analysis ||
      res.data ||
      res;

    return renderRealAIReportAnalysis(
      analysis
    );

  } catch (error) {

    console.error(
      "AI analysis load error:",
      error
    );

    return awaitingAnalysisBlock();
  }
}


function statusBadgeForReport(status){
  const map = {'Approved':'badge-success','Pending Review':'badge-warning','Correction Requested':'badge-danger','Draft':'badge-neutral'};
  return `<span class="badge ${map[status]||'badge-neutral'}">${status}</span>`;
}
function awaitingAnalysisBlock(){
  return `<div class="card"><div class="card-pad" style="text-align:center;color:var(--ink-soft);font-size:13px;"><i data-lucide="clock" style="width:16px;height:16px;vertical-align:-3px;margin-right:6px;"></i>AI analysis will run automatically once your supervisor opens this report.</div></div>`;
}


async function submitWeekReport(week) {

  const activities =
    document.getElementById("rf_activities")?.value.trim() || "";

  const skills =
    document.getElementById("rf_skills")?.value.trim() || "";

  const challenges =
    document.getElementById("rf_challenges")?.value.trim() || "";

  const comment =
    document.getElementById("rf_comment")?.value.trim() || "";

  if (!activities) {
    showToast({
      title: "Report incomplete",
      msg: "Please enter your activities.",
      tone: "danger",
      icon: "alert-circle"
    });
    return;
  }

  try {

    /* ---------------------------------------------------------------
       1. SUBMIT REPORT
       --------------------------------------------------------------- */

    const res = await API.submitWeeklyReport({
      week_number: Number(week),
      activities,
      skills_learned: skills,
      challenges,
      student_comment: comment
    });

    console.log("📝 WEEKLY REPORT:", res);

    if (!res.success) {
      throw new Error(
        res.message || "Unable to submit weekly report."
      );
    }

    const reportId = res.report_id;

    showToast({
      title: "Report submitted",
      msg: `Week ${week} report was submitted successfully.`,
      tone: "success",
      icon: "check-circle-2"
    });

    state.selectedWeek = Number(week);

    /* ---------------------------------------------------------------
       2. ANALYZE THE NEW REPORT WITH GEMINI
       --------------------------------------------------------------- */

    if (reportId) {

      const slot =
        document.getElementById("aiAnalysisSlot");

      if (slot) {

        slot.innerHTML = `
          <div class="card ai-card">

            <div
              class="card-pad ai-processing"
              style="padding:30px 20px;"
            >

              <div class="ai-orb">
                <i data-lucide="sparkles"></i>
              </div>

              <h3>AI is analysing your report</h3>

              <div
                class="step-line-text"
                id="reportAiStepText"
              >
                Reading your activities...
              </div>

            </div>

          </div>
        `;

        icons();
      }

      const analysis =
        await API.analyzeWeeklyReport(
          reportId
        );

      console.log(
        "🤖 AI REPORT ANALYSIS:",
        analysis
      );

      if (analysis?.success) {

        const aiData =
          analysis.analysis ||
          analysis.data ||
          analysis;

        if (slot) {

          slot.innerHTML =
            renderRealAIReportAnalysis(
              aiData
            );

          icons();
        }

        showToast({
          title: "AI analysis completed",
          msg: `Week ${week} has been analysed successfully.`,
          tone: "ai",
          icon: "sparkles"
        });

      } else {

        console.warn(
          "AI analysis failed:",
          analysis
        );

        if (slot) {
          slot.innerHTML =
            awaitingAnalysisBlock();
          icons();
        }
      }
    }

    /* ---------------------------------------------------------------
       3. RELOAD LOGBOOK
       --------------------------------------------------------------- */

    await renderStudentLogbook();

  } catch (error) {

    console.error(
      "❌ Weekly report submission:",
      error
    );

    showToast({
      title: "Submission failed",
      msg: error.message,
      tone: "danger",
      icon: "alert-circle"
    });
  }
}
function renderRealAIReportAnalysis(ai) {

  const score =
    Number(
      ai.performance_score ??
      ai.score ??
      0
    );

  const skills =
    ai.skills_identified ||
    "No specific skills identified yet.";

  const strengths =
    ai.strengths ||
    "No strengths identified yet.";

  const weaknesses =
    ai.weaknesses ||
    "No weaknesses identified yet.";

  const recommendations =
    ai.recommendations ||
    "No recommendations available yet.";

  return `
    <div class="card ai-card">

      <div class="card-header">

        <span class="ai-badge">
          <i data-lucide="sparkles"></i>
          AI Report Analysis
        </span>

        <span class="badge badge-ai">
          ${score}%
        </span>

      </div>

      <div class="card-pad">

        <div class="grid grid-4">

          ${miniScore(
            "Performance",
            score
          )}

          ${miniScore(
            "Week",
            ai.week_number || "—"
          )}

          ${miniScore(
            "Report ID",
            ai.report_id || "—"
          )}

          ${miniScore(
            "Status",
            ai.status || "Analyzed"
          )}

        </div>

        <div class="field" style="margin-top:18px;">

          <label>Summary</label>

          <p style="
            font-size:13.5px;
            line-height:1.6;
            color:var(--ink-soft);
          ">
            ${escapeHtml(
              ai.summary ||
              "No summary available."
            )}
          </p>

        </div>

        <div class="field">

          <label>Skills identified</label>

          <p style="
            font-size:13.5px;
            line-height:1.6;
            color:var(--ink-soft);
          ">
            ${escapeHtml(skills)}
          </p>

        </div>

        <div class="field">

          <label>Strengths</label>

          <p style="
            font-size:13.5px;
            line-height:1.6;
            color:var(--ink-soft);
          ">
            ${escapeHtml(strengths)}
          </p>

        </div>

        <div class="field">

          <label>Weaknesses</label>

          <p style="
            font-size:13.5px;
            line-height:1.6;
            color:var(--ink-soft);
          ">
            ${escapeHtml(weaknesses)}
          </p>

        </div>

        <div class="field">

          <label>Recommendations</label>

          <p style="
            font-size:13.5px;
            line-height:1.6;
            color:var(--ink-soft);
          ">
            ${escapeHtml(recommendations)}
          </p>

        </div>

      </div>

    </div>
  `;
}
function miniScore(label,val){
  return `<div style="text-align:center;padding:12px;border-radius:10px;background:var(--surface-2);">
    <div style="font-family:var(--font-mono);font-weight:700;font-size:20px;color:var(--ai-1);">${val}%</div>
    <div style="font-size:11px;color:var(--ink-soft);">${label}</div>
  </div>`;
}

/* ---------------------------------------------------------------------
   WEEKLY REPORTS — HISTORY
   --------------------------------------------------------------------- */
async function renderStudentReports() {

  const content =
    document.getElementById(
      "studentContent"
    );

  content.innerHTML = `
    <div class="card">

      <div class="card-pad state-block">
        <div class="spinner"></div>
        <h4>Loading report history...</h4>
      </div>

    </div>
  `;

  try {

    const res =
      await API.getWeeklyReports();


    if (!res?.success) {

      throw new Error(
        res?.message ||
        "Unable to load report history."
      );
    }


    const reports =
      Array.isArray(res.reports)
        ? res.reports
        : [];


    window.studentReportHistory =
      reports;


    if (!reports.length) {

      content.innerHTML = `
        <div class="card">

          <div class="state-block">

            <div class="state-icon">
              <i data-lucide="file-text"></i>
            </div>

            <h4>No reports yet</h4>

            <p>
              Your submitted weekly reports will appear here.
            </p>

          </div>

        </div>
      `;

      icons();
      return;
    }


    content.innerHTML = `

      <div class="card">

        <div class="table-toolbar">

          <h3
            class="card-title"
            style="margin:0;"
          >
            Report History
          </h3>

          <div class="search-input">

            <i data-lucide="search"></i>

            <input
              class="input"
              id="studentReportSearch"
              placeholder="Search reports..."
              oninput="filterStudentReports()"
            >

          </div>

        </div>


        <div class="table-wrap">

          <table class="data-table">

            <thead>

              <tr>
                <th>Week</th>
                <th>Submitted</th>
                <th>AI Score</th>
                <th>Status</th>
                <th></th>
              </tr>

            </thead>

            <tbody id="studentReportsTableBody">

              ${renderStudentReportRows(
                reports
              )}

            </tbody>

          </table>

        </div>

      </div>

    `;

    icons();


  } catch (error) {

    console.error(
      "❌ Student report history error:",
      error
    );


    content.innerHTML = `

      <div class="card">

        <div class="state-block">

          <div class="state-icon">
            <i data-lucide="alert-circle"></i>
          </div>

          <h4>
            Unable to load report history
          </h4>

          <p>
            ${escapeHtml(
              error.message
            )}
          </p>

          <button
            class="btn btn-primary"
            onclick="
              renderStudentReports()
            "
          >
            Try Again
          </button>

        </div>

      </div>

    `;

    icons();
  }
}


function renderStudentReportRows(
  reports
) {

  return reports.map(report => {

    const score =
      report.ai_performance_score !==
        null &&
      report.ai_performance_score !==
        undefined
        ? Number(
            report.ai_performance_score
          )
        : null;


    return `

      <tr>

        <td data-label="Week">

          <span class="cell-primary">
            Week ${Number(
              report.week_number
            )}
          </span>

        </td>


        <td data-label="Submitted">

          ${
            report.submitted_at
              ? formatDate(
                  report.submitted_at
                )
              : "—"
          }

        </td>


        <td data-label="AI Score">

          ${
            score !== null
              ? `
                <span class="badge badge-ai">
                  ${score.toFixed(1)}%
                </span>
              `
              : `
                <span class="cell-sub">
                  Pending
                </span>
              `
          }

        </td>


        <td data-label="Status">

          ${statusBadgeForReport(
            report.status
          )}

        </td>


        <td data-label="">

          <button
            class="btn btn-outline btn-sm"
            onclick="
              studentNav('logbook');
              selectLogbookWeek(
                ${Number(
                  report.week_number
                )}
              )
            "
          >
            View
          </button>

        </td>

      </tr>

    `;

  }).join("");
}


function filterStudentReports() {

  const input =
    document.getElementById(
      "studentReportSearch"
    );

  const body =
    document.getElementById(
      "studentReportsTableBody"
    );

  if (!input || !body) return;


  const query =
    input.value
      .trim()
      .toLowerCase();


  const reports =
    Array.isArray(
      window.studentReportHistory
    )
      ? window.studentReportHistory
      : [];


  const filtered =
    reports.filter(report => {

      const text =
        [
          report.week_number,
          report.activities,
          report.skills_learned,
          report.challenges,
          report.status,
          report.submitted_at
        ]
          .join(" ")
          .toLowerCase();

      return text.includes(query);

    });


  body.innerHTML =
    renderStudentReportRows(
      filtered
    );

  icons();
}

/* ---------------------------------------------------------------------
   SUPERVISOR (student-facing view)
   --------------------------------------------------------------------- */
async function renderStudentSupervisorPage() {

  const content =
    document.getElementById(
      "studentContent"
    );

  content.innerHTML = `
    <div class="card">

      <div class="card-pad state-block">

        <div class="spinner"></div>

        <h4>
          Loading supervisor information...
        </h4>

      </div>

    </div>
  `;


  try {

    const [
      placementRes,
      reportsRes
    ] = await Promise.all([

      API.getPlacement(),

      API.getWeeklyReports()

    ]);


    if (!placementRes?.success) {

      throw new Error(
        placementRes?.message ||
        "Unable to load placement."
      );
    }


    const placement =
      placementRes.placement ||
      placementRes.data ||
      {};


    const reports =
      reportsRes?.success &&
      Array.isArray(
        reportsRes.reports
      )
        ? reportsRes.reports
        : [];


    const supervisorName =
      placement.supervisor_name ||
      placement.supervisor?.full_name ||
      placement.supervisor?.name ||
      "Supervisor not assigned";


    const supervisorEmail =
      placement.supervisor_email ||
      placement.supervisor?.email ||
      "—";


    const supervisorPhone =
      placement.supervisor_phone ||
      placement.supervisor?.phone ||
      "—";


    const supervisorDepartment =
      placement.supervisor_department ||
      placement.supervisor?.department ||
      "—";


    const recentFeedback =
      reports
        .filter(
          report =>
            String(
              report.supervisor_comment ||
              ""
            ).trim() !== ""
        )
        .sort(
          (a, b) =>
            Number(
              b.week_number
            ) -
            Number(
              a.week_number
            )
        )
        .slice(0, 5);


    content.innerHTML = `

      <div class="card">

        <div
          class="card-pad"
          style="
            display:flex;
            gap:20px;
            align-items:center;
            flex-wrap:wrap;
          "
        >

          <div
            class="avatar"
            style="
              width:64px;
              height:64px;
              font-size:20px;
            "
          >
            ${getInitials(
              supervisorName
            )}
          </div>


          <div
            style="
              flex:1;
              min-width:220px;
            "
          >

            <h3 style="margin:0;">
              ${escapeHtml(
                supervisorName
              )}
            </h3>

            <p
              class="cell-sub"
              style="margin:3px 0;"
            >
              ${escapeHtml(
                supervisorDepartment
              )}
            </p>

            <p
              class="cell-sub"
              style="margin:0;"
            >
              ${escapeHtml(
                supervisorEmail
              )}

              ${
                supervisorPhone !== "—"
                  ? " · " +
                    escapeHtml(
                      supervisorPhone
                    )
                  : ""
              }

            </p>

          </div>

        </div>

      </div>


      <div style="height:18px;"></div>


      <div class="card">

        <div class="card-header">

          <div>

            <h3 class="card-title">
              Supervisor Feedback
            </h3>

            <p class="cell-sub">
              Feedback submitted on your weekly reports.
            </p>

          </div>

        </div>


        <div class="card-pad">

          ${
            recentFeedback.length
              ? recentFeedback
                  .map(
                    report => `

                      <div
                        style="
                          padding:12px 0;
                          border-bottom:
                            1px solid var(--border-soft);
                        "
                      >

                        <strong
                          style="
                            font-size:13px;
                          "
                        >
                          Week ${Number(
                            report.week_number
                          )}
                        </strong>

                        <p
                          class="cell-sub"
                          style="
                            margin:5px 0 0;
                            line-height:1.6;
                          "
                        >
                          ${escapeHtml(
                            report.supervisor_comment
                          )}
                        </p>

                      </div>

                    `
                  )
                  .join("")
              : `

                <div class="state-block">

                  <div class="state-icon">
                    <i data-lucide="message-square"></i>
                  </div>

                  <h4>
                    No supervisor feedback yet
                  </h4>

                  <p>
                    Feedback will appear here after your supervisor reviews a report.
                  </p>

                </div>

              `
          }

        </div>

      </div>

    `;


    icons();


  } catch (error) {

    console.error(
      "❌ Student supervisor error:",
      error
    );


    content.innerHTML = `

      <div class="card">

        <div class="state-block">

          <div class="state-icon">
            <i data-lucide="alert-circle"></i>
          </div>

          <h4>
            Unable to load supervisor
          </h4>

          <p>
            ${escapeHtml(
              error.message
            )}
          </p>

        </div>

      </div>

    `;

    icons();
  }
}


/* ---------------------------------------------------------------------
   AI INSIGHTS
   --------------------------------------------------------------------- */
async function renderStudentAIInsights() {

  const content =
    document.getElementById("studentContent");

  content.innerHTML = `
    <div class="card">
      <div class="state-block">
        <div class="spinner"></div>
        <h4>Loading AI insights...</h4>
        <p>Analyzing your weekly performance.</p>
      </div>
    </div>
  `;

  try {

    const res = await API.getWeeklyReports();

    if (!res?.success) {
      throw new Error(
        res?.message || "Unable to load weekly reports."
      );
    }

    const reports =
      Array.isArray(res.reports) ? res.reports : [];

    const analyzed = reports.filter(
      r =>
        r.ai_analysis_id ||
        r.ai_performance_score !== null
    );

    if (!analyzed.length) {

      content.innerHTML = `
        <div class="card ai-card">
          <div class="state-block">
            <div class="state-icon">
              <i data-lucide="brain-circuit"></i>
            </div>
            <h4>AI insights are not available yet</h4>
            <p>
              Your reports need AI analysis before insights can be shown.
            </p>
          </div>
        </div>
      `;

      icons();
      return;
    }

    const scores = analyzed.map(
      r => Number(r.ai_performance_score || 0)
    );

    const average =
      scores.reduce((a, b) => a + b, 0) /
      scores.length;

    const latest =
      scores[scores.length - 1];

    const trend =
      latest - scores[0];

    const latestReport =
      analyzed[analyzed.length - 1];

    content.innerHTML = `

      <div class="grid grid-3" style="margin-bottom:18px;">

        ${statCard(
          "Average AI Score",
          average.toFixed(1) + "%",
          "brain-circuit",
          null,
          null
        )}

        ${statCard(
          "Latest AI Score",
          latest.toFixed(1) + "%",
          "sparkles",
          null,
          null
        )}

        ${statCard(
          "Performance Trend",
          (trend >= 0 ? "+" : "") +
            trend.toFixed(1),
          trend >= 0
            ? "trending-up"
            : "trending-down",
          null,
          null
        )}

      </div>

      <div class="card ai-card">

        <div class="card-header">
          <span class="ai-badge">
            <i data-lucide="brain-circuit"></i>
            AI Progress Insight
          </span>
        </div>

        <div class="card-pad">
          <canvas id="aiTrendChart" height="100"></canvas>
        </div>

      </div>

      <div class="grid grid-2" style="margin-top:18px;">

        <div class="card">

          <div class="card-header">
            <h3 class="card-title">
              Latest Analysis
            </h3>
          </div>

          <div class="card-pad">

            ${profField(
              "Summary",
              latestReport.ai_summary || "—"
            )}

            ${profField(
              "Skills",
              latestReport.ai_skills_identified || "—"
            )}

            ${profField(
              "Strengths",
              latestReport.ai_strengths || "—"
            )}

          </div>

        </div>

        <div class="card">

          <div class="card-header">
            <h3 class="card-title">
              Improvement Areas
            </h3>
          </div>

          <div class="card-pad">

            ${profField(
              "Weaknesses",
              latestReport.ai_weaknesses || "—"
            )}

            ${profField(
              "Recommendations",
              latestReport.ai_recommendations || "—"
            )}

          </div>

        </div>

      </div>
    `;

    icons();

    new Chart(
      document.getElementById("aiTrendChart"),
      {
        type: "line",
        data: {
          labels: analyzed.map(
            r => "W" + r.week_number
          ),
          datasets: [{
            label: "AI Performance",
            data: scores,
            tension: 0.35,
            fill: true
          }]
        },
        options: chartOpts()
      }
    );

  } catch (error) {

    console.error(
      "❌ Student AI insights error:",
      error
    );

    content.innerHTML = `
      <div class="card">
        <div class="state-block">
          <h4>Unable to load AI insights</h4>
          <p>${escapeHtml(error.message)}</p>
        </div>
      </div>
    `;

    icons();
  }
}

function aiObs(icon,title,msg,tone){
  const c = {success:'var(--success)',ai:'var(--ai-1)',warning:'var(--warning)'}[tone];
  const bg = {success:'var(--success-tint)',ai:'var(--ai-tint)',warning:'var(--warning-tint)'}[tone];
  return `<div style="display:flex;gap:12px;"><div style="width:32px;height:32px;border-radius:9px;background:${bg};color:${c};display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i data-lucide="${icon}" style="width:15px;height:15px;"></i></div>
    <div><div style="font-size:13px;font-weight:600;">${title}</div><div style="font-size:12.5px;color:var(--ink-soft);">${msg}</div></div></div>`;
}
function chartOpts(){
  return {responsive:true, interaction:{mode:'index',intersect:false}, plugins:{legend:{position:'bottom',labels:{boxWidth:10,font:{size:11}}}}, scales:{y:{beginAtZero:true,max:100,grid:{color:'rgba(120,130,150,.12)'}}, x:{grid:{display:false}}}};
}

/* ---------------------------------------------------------------------------
   FINAL EVALUATION
   --------------------------------------------------------------------- */
async function renderSupervisorEvaluations() {

  const content =
    document.getElementById("supervisorContent");

  content.innerHTML = `
    <div class="card">
      <div class="state-block">
        <div class="spinner"></div>
        <h4>Loading eligible students...</h4>
        <p>Checking completed training records.</p>
      </div>
    </div>
  `;

  try {

    const res =
      await API.getSupervisorWeeklyReports();

    if (!res?.success) {
      throw new Error(
        res?.message ||
        "Unable to load students."
      );
    }

    const reports =
      Array.isArray(res.reports)
        ? res.reports
        : [];

    const grouped = {};

    reports.forEach(report => {

      const id =
        Number(report.student_id);

      if (!id) return;

      if (!grouped[id]) {
        grouped[id] = {
          placement_id:
            Number(report.placement_id),

          student_id:
            id,

          full_name:
            report.full_name,

          matric_no:
            report.matric_no,

          department:
            report.department,

          programme:
            report.programme,

          start_date:
            report.start_date,

          end_date:
            report.end_date,

          placement_status:
            report.placement_status,

          report_count:
            0,

          approved_count:
            0
        };
      }

      grouped[id].report_count++;

      if (
        String(report.status).toLowerCase() ===
        "approved"
      ) {
        grouped[id].approved_count++;
      }

    });

    const students =
      Object.values(grouped);


    /*
    |--------------------------------------------------------------------------
    | Only allow final evaluation when training is completed
    |--------------------------------------------------------------------------
    */

    const today =
      new Date();

    const eligibleStudents =
      students.filter(student => {

        const endDate =
          student.end_date
            ? new Date(
                student.end_date + "T23:59:59"
              )
            : null;

        const completedByDate =
          endDate &&
          endDate <= today;

        const completedByStatus =
          String(
            student.placement_status || ""
          ).toLowerCase() ===
          "completed";

        return (
          completedByDate ||
          completedByStatus
        );
      });


    if (!eligibleStudents.length) {

      content.innerHTML = `
        <div class="card">

          <div class="state-block">

            <div class="state-icon">
              <i data-lucide="clipboard-check"></i>
            </div>

            <h4>
              No final evaluations available
            </h4>

            <p>
              Final evaluation becomes available
              after a student's training period is completed.
            </p>

          </div>

        </div>
      `;

      icons();
      return;
    }


    content.innerHTML = `

      <div class="card">

        <div class="card-header">

          <div>

            <h3 class="card-title">
              Final Evaluations
            </h3>

            <p class="cell-sub">
              Evaluate students who have completed their training.
            </p>

          </div>

        </div>


        <div class="card-pad">

          ${eligibleStudents.map(student => `

            <div
              class="card"
              style="
                margin-bottom:10px;
                border:1px solid var(--border);
              "
            >

              <div
                style="
                  display:flex;
                  justify-content:space-between;
                  align-items:center;
                  gap:16px;
                "
              >

                <div>

                  <strong>
                    ${escapeHtml(
                      student.full_name
                    )}
                  </strong>

                  <div class="cell-sub">
                    ${escapeHtml(
                      student.matric_no || ""
                    )}
                  </div>

                  <div class="cell-sub">
                    ${escapeHtml(
                      student.programme || ""
                    )}
                  </div>

                  <div class="cell-sub">
                    Training:
                    ${formatDate(student.start_date)}
                    →
                    ${formatDate(student.end_date)}
                  </div>

                </div>


                <button
                  class="btn btn-primary btn-sm"
                  onclick="
                    openSupervisorEvaluation(
                      ${student.placement_id}
                    )
                  "
                >
                  Evaluate
                </button>

              </div>

            </div>

          `).join("")}

        </div>

      </div>

    `;

    icons();

  } catch (error) {

    console.error(
      "❌ Evaluation loading error:",
      error
    );

    content.innerHTML = `
      <div class="card">

        <div class="state-block">

          <div class="state-icon">
            <i data-lucide="alert-circle"></i>
          </div>

          <h4>
            Unable to load evaluations
          </h4>

          <p>
            ${escapeHtml(error.message)}
          </p>

          <button
            class="btn btn-primary"
            onclick="renderSupervisorEvaluations()"
          >
            Try Again
          </button>

        </div>

      </div>
    `;

    icons();
  }
}

function renderRealFinalEvaluation(ev) {

  const overall =
    Number(
      ev.overall_score ??
      ev.overall ??
      0
    );

  const skills =
    ev.skills_developed ||
    "";

  const strengths =
    ev.strengths ||
    "";

  const weaknesses =
    ev.weaknesses ||
    "";

  const summary =
    ev.ai_summary ||
    ev.summary ||
    "";

  const recommendation =
    ev.recommendation ||
    "";

  document.getElementById(
    "studentContent"
  ).innerHTML = `

    <div
      class="card ai-card"
      style="margin-bottom:18px;"
    >

      <div class="card-header">

        <span class="ai-badge">
          <i data-lucide="award"></i>
          Final Performance Evaluation
        </span>

      </div>

      <div
        class="card-pad"
        style="
          display:flex;
          gap:24px;
          flex-wrap:wrap;
          align-items:center;
        "
      >

        ${gaugeSVG(overall)}

        <div style="flex:1;min-width:260px;">

          <h3>
            Overall performance:
            ${overall}%
          </h3>

          <p
            style="
              font-size:13.5px;
              color:var(--ink-soft);
              line-height:1.6;
            "
          >
            ${escapeHtml(summary)}
          </p>

        </div>

      </div>

    </div>

    <div class="grid grid-2">

      <div class="card">

        <div class="card-header">
          <h3 class="card-title">
            Scores
          </h3>
        </div>

        <div class="card-pad">

          ${miniScore(
            "Supervisor score",
            Number(ev.supervisor_score || 0)
          )}

          <div style="height:10px;"></div>

          ${miniScore(
            "AI score",
            Number(ev.ai_score || 0)
          )}

          <div style="height:10px;"></div>

          ${miniScore(
            "Overall score",
            overall
          )}

        </div>

      </div>

      <div class="card">

        <div class="card-header">
          <h3 class="card-title">
            Skills developed
          </h3>
        </div>

        <div class="card-pad">

          <p
            style="
              color:var(--ink-soft);
              line-height:1.6;
              font-size:13.5px;
            "
          >
            ${escapeHtml(skills || "—")}
          </p>

        </div>

      </div>

      <div class="card">

        <div class="card-header">
          <h3 class="card-title">
            Strengths
          </h3>
        </div>

        <div class="card-pad">

          <p
            style="
              color:var(--ink-soft);
              line-height:1.6;
              font-size:13.5px;
            "
          >
            ${escapeHtml(strengths || "—")}
          </p>

        </div>

      </div>

      <div class="card">

        <div class="card-header">
          <h3 class="card-title">
            Areas for improvement
          </h3>
        </div>

        <div class="card-pad">

          <p
            style="
              color:var(--ink-soft);
              line-height:1.6;
              font-size:13.5px;
            "
          >
            ${escapeHtml(weaknesses || "—")}
          </p>

        </div>

      </div>

    </div>

    <div
      class="card"
      style="margin-top:18px;"
    >

      <div class="card-header">

        <h3 class="card-title">
          Final recommendation
        </h3>

      </div>

      <div
        class="card-pad"
        style="
          font-size:13.5px;
          color:var(--ink-soft);
          line-height:1.7;
        "
      >
        ${escapeHtml(
          recommendation || "—"
        )}
      </div>

    </div>

  `;

  icons();
}

function renderFinalEvaluation(ev){
  document.getElementById('studentContent').innerHTML = `
    <div class="card ai-card" style="margin-bottom:18px;">
      <div class="card-header"><span class="ai-badge"><i data-lucide="award"></i>AI Final Performance Evaluation</span></div>
      <div class="card-pad" style="display:flex;gap:24px;flex-wrap:wrap;align-items:center;">
        ${gaugeSVG(ev.overall)}
        <div style="flex:1;min-width:260px;">
          <h3 style="margin:0 0 8px;">Overall performance: ${ev.overall}%</h3>
          <p style="font-size:13.5px;color:var(--ink-soft);margin:0;">${ev.summary}</p>
        </div>
      </div>
    </div>
    <div class="grid grid-2">
      <div class="card"><div class="card-header"><h3 class="card-title">Performance breakdown</h3></div>
        <div class="card-pad">${ev.breakdown.map(b=>`<div class="progress-row"><span class="label">${b.label}</span><span class="val">${b.value}%</span></div><div class="progress-track" style="margin-bottom:12px;"><div class="progress-fill ai" style="width:${b.value}%;"></div></div>`).join('')}</div>
      </div>
      <div class="card"><div class="card-header"><h3 class="card-title">Skills acquired</h3></div>
        <div class="card-pad"><div class="chip-select">${ev.skills.map(s=>`<span class="skill-tag">${s}</span>`).join('')}</div></div>
      </div>
      <div class="card"><div class="card-header"><h3 class="card-title">Strengths</h3></div>
        <div class="card-pad" style="display:flex;flex-direction:column;gap:10px;">${ev.strengths.map(s=>`<div style="display:flex;gap:8px;font-size:13.5px;"><i data-lucide="check-circle-2" style="width:15px;height:15px;color:var(--success);flex-shrink:0;margin-top:1px;"></i>${s}</div>`).join('')}</div>
      </div>
      <div class="card"><div class="card-header"><h3 class="card-title">Areas for improvement</h3></div>
        <div class="card-pad" style="display:flex;flex-direction:column;gap:10px;">${ev.improvements.map(s=>`<div style="display:flex;gap:8px;font-size:13.5px;"><i data-lucide="arrow-up-circle" style="width:15px;height:15px;color:var(--warning);flex-shrink:0;margin-top:1px;"></i>${s}</div>`).join('')}</div>
      </div>
    </div>
    <div class="card" style="margin-top:18px;"><div class="card-header"><h3 class="card-title">AI recommendation</h3></div>
      <div class="card-pad" style="font-size:13.5px;color:var(--ink-soft);">${ev.recommendation}
        <div style="margin-top:14px;"><button class="btn btn-outline btn-sm" onclick="showToast({title:'Report downloaded', tone:'success', icon:'download'})"><i data-lucide="download" style="width:14px;height:14px;"></i> Download final report (PDF)</button></div>
      </div>
    </div>`;
  icons();
}
async function renderStudentEvaluation() {

  const content =
    document.getElementById("studentContent");

  content.innerHTML = `
    <div class="card">
      <div class="state-block">
        <div class="spinner"></div>
        <h4>Loading final evaluation...</h4>
        <p>Checking your evaluation status.</p>
      </div>
    </div>
  `;

  try {

    const res =
      await API.getStudentFinalEvaluation();

    if (!res?.success) {

      content.innerHTML = `
        <div class="card">
          <div class="state-block">
            <div class="state-icon">
              <i data-lucide="lock"></i>
            </div>

            <h4>
              Final evaluation not available
            </h4>

            <p>
              Your training must be completed and
              your supervisor must submit the final evaluation.
            </p>
          </div>
        </div>
      `;

      icons();
      return;
    }

    const ev =
      res.evaluation;

    content.innerHTML = `
      <div class="card ai-card">

        <div class="card-header">
          <span class="ai-badge">
            <i data-lucide="award"></i>
            Final Performance Evaluation
          </span>
        </div>

        <div class="card-pad">

          <h3>
            ${escapeHtml(ev.student_name)}
          </h3>

          <p class="cell-sub">
            ${escapeHtml(ev.organization_name)}
          </p>

          <div class="grid grid-3">

            <div class="stat-card">
              <div class="cell-sub">
                Supervisor Score
              </div>
              <strong>
                ${Number(ev.supervisor_score).toFixed(1)}%
              </strong>
            </div>

            <div class="stat-card">
              <div class="cell-sub">
                AI Score
              </div>
              <strong>
                ${ev.ai_score !== null
                  ? Number(ev.ai_score).toFixed(1) + "%"
                  : "—"}
              </strong>
            </div>

            <div class="stat-card">
              <div class="cell-sub">
                Overall Score
              </div>
              <strong>
                ${ev.overall_score !== null
                  ? Number(ev.overall_score).toFixed(1) + "%"
                  : "—"}
              </strong>
            </div>

          </div>

          ${profField(
            "AI Summary",
            ev.ai_summary || "—"
          )}

          ${profField(
            "Skills Developed",
            ev.skills_developed || "—"
          )}

          ${profField(
            "Strengths",
            ev.strengths || "—"
          )}

          ${profField(
            "Areas for Improvement",
            ev.weaknesses || "—"
          )}

          ${profField(
            "Recommendation",
            ev.recommendation || "—"
          )}

        </div>
      </div>
    `;

    icons();

  } catch (error) {

    console.error(
      "❌ Final evaluation error:",
      error
    );

    content.innerHTML = `
      <div class="card">
        <div class="state-block">
          <div class="state-icon">
            <i data-lucide="alert-circle"></i>
          </div>

          <h4>
            Unable to load final evaluation
          </h4>

          <p>
            ${escapeHtml(error.message)}
          </p>
        </div>
      </div>
    `;

    icons();
  }
}


function updateTopbarUser(user, role, student = null) {

  if (!user) return;


  const fullName =
    user.full_name ||
    user.name ||
    student?.full_name ||
    "User";


  const initials =
    getInitials(fullName);


  if (role === "student") {

    const avatar =
      document.getElementById(
        "studentTopbarAvatar"
      );

    const name =
      document.getElementById(
        "studentTopbarName"
      );

    const roleText =
      document.getElementById(
        "studentTopbarRole"
      );


    if (avatar) {
      avatar.textContent =
        initials;
    }


    if (name) {
      name.textContent =
        fullName;
    }


    if (roleText) {

      roleText.textContent =
        student?.matric_no ||
        student?.reg_no ||
        user.matric_no ||
        "Student";

    }

  }


  if (role === "supervisor") {

    const avatar =
      document.getElementById(
        "supervisorTopbarAvatar"
      );

    const name =
      document.getElementById(
        "supervisorTopbarName"
      );

    const roleText =
      document.getElementById(
        "supervisorTopbarRole"
      );


    if (avatar) {
      avatar.textContent =
        initials;
    }


    if (name) {
      name.textContent =
        fullName;
    }


    if (roleText) {

      roleText.textContent =
        user.organization_name ||
        user.organization ||
        user.department ||
        "Supervisor";

    }

  }
}
/* ---------------------------------------------------------------------
   NOTIFICATIONS PAGE / SETTINGS
   --------------------------------------------------------------------- */
async function renderStudentNotifications() {

  await renderNotifPage(
    "student"
  );
}


async function renderNotifPage(role) {

  const target =
    document.getElementById(
      role + "Content"
    );

  if (!target) return;

  target.innerHTML = `
    <div class="card">

      <div class="card-pad state-block">
        <div class="spinner"></div>
        <p>Loading notifications...</p>
      </div>

    </div>
  `;

  try {

    const res =
      await API.getNotifications();

    console.log(
      "🔔 NOTIFICATIONS:",
      res
    );

    if (!res.success) {

      throw new Error(
        res.message ||
        "Unable to load notifications."
      );
    }

    const notifications =
      res.notifications ||
      res.data ||
      [];

    target.innerHTML = `
      <div class="card">

        <div class="card-header">

          <div>

            <h3 class="card-title">
              Notifications
            </h3>

            <div class="cell-sub">
              Your latest AI-ITMS updates
            </div>

          </div>

          <button
            class="btn btn-ghost btn-sm"
            onclick="markAllNotificationsRead()"
          >
            Mark all read
          </button>

        </div>

        <div
          class="card-pad"
          style="
            display:flex;
            flex-direction:column;
            gap:4px;
          "
        >

          ${
            notifications.length
              ? notifications.map(n => {

                  const isRead =
                    Number(
                      n.is_read ??
                      n.read_status ??
                      0
                    );

                  return `
                    <div
                      style="
                        display:flex;
                        gap:12px;
                        padding:14px 8px;
                        border-bottom:
                          1px solid
                          var(--border-soft);
                        ${
                          !isRead
                            ? "background:var(--surface-2);"
                            : ""
                        }
                      "
                      onclick="markNotificationRead(${n.id})"
                    >

                      <div
                        style="
                          width:36px;
                          height:36px;
                          border-radius:10px;
                          display:flex;
                          align-items:center;
                          justify-content:center;
                          background:var(--surface-2);
                        "
                      >
                        <i
                          data-lucide="${
                            n.icon || "bell"
                          }"
                        ></i>
                      </div>

                      <div style="flex:1;">

                        <div
                          style="
                            font-size:13.5px;
                            font-weight:600;
                          "
                        >
                          ${
                            escapeHtml(
                              n.title ||
                              "Notification"
                            )
                          }
                        </div>

                        <div
                          style="
                            font-size:12.5px;
                            color:var(--ink-soft);
                            margin-top:3px;
                          "
                        >
                          ${
                            escapeHtml(
                              n.message ||
                              n.msg ||
                              ""
                            )
                          }
                        </div>

                        <div
                          style="
                            font-size:11px;
                            color:var(--ink-faint);
                            margin-top:5px;
                          "
                        >
                          ${
                            escapeHtml(
                              n.created_at ||
                              n.time ||
                              ""
                            )
                          }
                        </div>

                      </div>

                      ${
                        !isRead
                          ? `<span class="nav-dot"></span>`
                          : ""
                      }

                    </div>
                  `;
                }).join("")

              : `
                <div class="state-block">

                  <div class="state-icon">
                    <i data-lucide="bell-off"></i>
                  </div>

                  <h4>
                    No notifications yet
                  </h4>

                  <p>
                    You'll see placement,
                    report and AI updates here.
                  </p>

                </div>
              `
          }

        </div>

      </div>
    `;

    icons();

    await updateStudentNotificationCount();

  } catch (error) {

    console.error(
      "❌ Notifications error:",
      error
    );

    target.innerHTML = `
      <div class="card">

        <div class="card-pad state-block">

          <div class="state-icon">
            <i data-lucide="alert-circle"></i>
          </div>

          <h4>
            Unable to load notifications
          </h4>

          <p>
            ${escapeHtml(error.message)}
          </p>

          <button
            class="btn btn-primary"
            onclick="studentNav('notifications')"
          >
            Try Again
          </button>

        </div>

      </div>
    `;

    icons();
  }
}


async function markNotificationRead(id) {

  try {

    const res =
      await API.markNotificationRead(id);

    if (res.success) {
      await renderStudentNotifications();
    }

  } catch (error) {

    console.error(
      "❌ Mark notification error:",
      error
    );
  }
}


async function updateStudentNotificationCount() {

  const dot =
    document.getElementById(
      "studentNotifDot"
    );

  if (!dot) return;

  try {

    const res =
      await API.getUnreadNotificationCount();

    const count =
      Number(
        res.count ??
        res.unread_count ??
        0
      );

    dot.style.display =
      count > 0
        ? "inline-block"
        : "none";

  } catch (error) {

    console.error(
      "❌ Notification count error:",
      error
    );
  }
}


async function markAllNotificationsRead() {

  showToast({
    title: "Notifications",
    msg: "Individual notification read status is available.",
    tone: "info",
    icon: "bell"
  });
}
function renderStudentSettings(){ renderSettingsPage('student'); }

function renderSettingsPage(role) {

  const container =
    document.getElementById(
      role + "Content"
    );

  if (!container) return;

  const savedPreference =
    localStorage.getItem(
      `AI-ITMS_notification_preference_${role}`
    ) || "important";

  container.innerHTML = `

    <div
      class="grid grid-2"
      style="gap:18px;"
    >

      <!-- =========================================================
           NOTIFICATION PREFERENCES
           ========================================================= -->

      <div class="card">

        <div class="card-header">

          <div>

            <h3 class="card-title">
              Preferences
            </h3>

            <p class="cell-sub">
              Manage your notification preferences.
            </p>

          </div>

          <i
            data-lucide="bell"
            style="
              width:18px;
              height:18px;
              color:var(--ink-faint);
            "
          ></i>

        </div>


        <div class="card-pad">

          <div class="field">

            <label>
              Email notifications
            </label>

            <select
              class="input"
              id="${role}NotificationPreference"
            >

              <option
                value="all"
                ${
                  savedPreference === "all"
                    ? "selected"
                    : ""
                }
              >
                All activity
              </option>

              <option
                value="important"
                ${
                  savedPreference === "important"
                    ? "selected"
                    : ""
                }
              >
                Important only
              </option>

              <option
                value="off"
                ${
                  savedPreference === "off"
                    ? "selected"
                    : ""
                }
              >
                Off
              </option>

            </select>

          </div>


          <button
            class="btn btn-primary"
            onclick="
              saveLocalSettings('${role}')
            "
          >

            <i
              data-lucide="save"
              style="
                width:14px;
                height:14px;
              "
            ></i>

            Save settings

          </button>

        </div>

      </div>


      <!-- =========================================================
           APPEARANCE
           ========================================================= -->

      <div class="card">

        <div class="card-header">

          <div>

            <h3 class="card-title">
              Appearance
            </h3>

            <p class="cell-sub">
              Choose your preferred portal theme.
            </p>

          </div>

          <i
            data-lucide="palette"
            style="
              width:18px;
              height:18px;
              color:var(--ink-faint);
            "
          ></i>

        </div>


        <div class="card-pad">

          <div class="field">

            <label>
              Theme
            </label>

            <div class="chip-select">

              <div
                class="chip ${
                  state.theme === "light"
                    ? "selected"
                    : ""
                }"
                onclick="
                  setTheme('light')
                "
              >

                <i
                  data-lucide="sun"
                  style="
                    width:13px;
                    height:13px;
                    vertical-align:-2px;
                  "
                ></i>

                Light

              </div>


              <div
                class="chip ${
                  state.theme === "dark"
                    ? "selected"
                    : ""
                }"
                onclick="
                  setTheme('dark')
                "
              >

                <i
                  data-lucide="moon"
                  style="
                    width:13px;
                    height:13px;
                    vertical-align:-2px;
                  "
                ></i>

                Dark

              </div>

            </div>

          </div>


          <div
            style="
              padding:12px;
              border-radius:10px;
              background:var(--surface-2);
              color:var(--ink-soft);
              font-size:12px;
              line-height:1.5;
            "
          >

            Theme changes are applied immediately.

          </div>

        </div>

      </div>

    </div>

  `;

  icons();
}


function saveLocalSettings(role) {

  const preference =
    document.getElementById(
      `${role}NotificationPreference`
    )?.value || "important";


  localStorage.setItem(
    `AI-ITMS_notification_preference_${role}`,
    preference
  );


  showToast({

    title:
      "Settings saved",

    msg:
      "Your notification preference has been saved.",

    tone:
      "success",

    icon:
      "check-circle-2"

  });
}


function setTheme(theme) {

  if (
    theme !== "light" &&
    theme !== "dark"
  ) {
    return;
  }


  if (
    state.theme !== theme
  ) {
    toggleTheme();
  }


  const studentScreen =
    document.getElementById(
      "screen-student"
    );


  const currentRole =
    studentScreen &&
    !studentScreen.classList.contains(
      "hidden"
    )
      ? "student"
      : "supervisor";


  renderSettingsPage(
    currentRole
  );
}


function pushNotif(role, title, msg, icon, tone){
  DB.notifications.unshift({id:Date.now(), for:role, title, msg, time:'Just now', icon, tone});
  showToast({title, msg, tone, icon});
}

/* =====================================================================
   SUPERVISOR PORTAL
   ===================================================================== */
const supervisorTitles = {

  dashboard:
    "Dashboard",

  placements:
    "Placement Approvals",

  students:
    "My Students",

  reports:
    "Weekly Reports",

  evaluations:
    "Evaluations",

  "ai-insights":
    "AI Insights",

  notifications:
    "Notifications",

  profile:
    "Profile",

  settings:
    "Settings"

};


function supervisorNav(sec) {

  document
    .querySelectorAll(
      "#supervisorSidebar .nav-item"
    )
    .forEach(
      el =>
        el.classList.toggle(
          "active",
          el.dataset.sec === sec
        )
    );


  document
    .getElementById(
      "supervisorTitle"
    )
    .textContent =
      supervisorTitles[sec] ||
      "Dashboard";


  toggleSidebar(
    "supervisor",
    false
  );


  const map = {

    dashboard:
      renderSupervisorDashboard,

    placements:
      renderSupervisorPlacements,

    students:
      renderSupervisorStudents,

    reports:
      renderSupervisorReports,

    evaluations:
      renderSupervisorEvaluations,

    "ai-insights":
      renderSupervisorAIInsights,

    notifications:
      () =>
        renderNotifPage(
          "supervisor"
        ),

    profile:
      renderSupervisorProfile,

    settings:
      () =>
        renderSettingsPage(
          "supervisor"
        )

  };


  (
    map[sec] ||
    renderSupervisorDashboard
  )();


  window.scrollTo(
    0,
    0
  );
}

/* ---------------------------------------------------------------------
   PLACEMENT APPROVALS — supervisor confirms AI-recommended placements
   (this step was handled by an admin role in the original spec; with
   the portal scoped to Student + Supervisor only, the supervisor now
   signs off on placements into their own organization).
   --------------------------------------------------------------------- */

async function renderSupervisorPlacements() {

  const content =
    document.getElementById(
      "supervisorContent"
    );

  if (!content) return;


  content.innerHTML = `
    <div class="card">
      <div class="card-pad state-block">
        <div class="state-icon">
          <i data-lucide="loader-circle"></i>
        </div>
        <h4>Loading placements...</h4>
        <p>
          Fetching placement requests assigned to you.
        </p>
      </div>
    </div>
  `;

  icons();


  try {

    const res =
      await API.getPendingPlacements();

    console.log(
      "📋 PENDING PLACEMENTS:",
      res
    );


    if (!res?.success) {

      throw new Error(
        res?.message ||
        "Unable to load pending placements."
      );
    }


    const rows =
      res.placements || [];


    if (!rows.length) {

      content.innerHTML = `
        <div class="card">
          <div class="state-block">

            <div class="state-icon">
              <i data-lucide="check-check"></i>
            </div>

            <h4>
              No placements waiting
            </h4>

            <p>
              New student placement requests
              assigned to you will appear here.
            </p>

          </div>
        </div>
      `;

      icons();

      return;
    }


    content.innerHTML = `

      <div class="card">

        <div class="card-header">

          <h3 class="card-title">
            Placement Approvals
          </h3>

          <span class="badge badge-warning">
            ${rows.length} pending
          </span>

        </div>


        <div
          class="card-pad"
          style="
            display:flex;
            flex-direction:column;
            gap:14px;
          "
        >

          ${rows.map(p => `

            <div
              class="card ai-card"
              style="padding:16px;"
            >

              <div
                style="
                  display:flex;
                  justify-content:space-between;
                  flex-wrap:wrap;
                  gap:12px;
                  align-items:center;
                "
              >

                <div>

                  <span
                    class="ai-badge"
                    style="margin-bottom:6px;"
                  >
                    <i data-lucide="sparkles"></i>

                    ${
                      Number(p.ai_match || 0)
                    }% Match

                  </span>


                  <h4
                    style="
                      margin:8px 0 2px;
                    "
                  >
                    ${escapeHtml(
                      p.student_name
                    )}
                  </h4>


                  <p
                    class="cell-sub"
                    style="margin:0;"
                  >
                    ${escapeHtml(
                      p.programme
                    )}
                    →
                    ${escapeHtml(
                      p.organization_name
                    )}
                  </p>

                </div>


                <div
                  style="
                    display:flex;
                    gap:8px;
                  "
                >

                  <button
                    class="btn btn-outline btn-sm btn-danger"
                    onclick="rejectSupervisorPlacement(${p.placement_id})"
                  >
                    Reject
                  </button>


                  <button
                    class="btn btn-success btn-sm"
                    onclick="approveSupervisorPlacement(${p.placement_id})"
                  >

                    <i
                      data-lucide="check"
                      style="
                        width:14px;
                        height:14px;
                      "
                    ></i>

                    Approve

                  </button>

                </div>

              </div>


              <p
                style="
                  font-size:13px;
                  color:var(--ink-soft);
                  margin:12px 0 0;
                "
              >

                <strong
                  style="color:var(--ink);"
                >
                  AI reasoning:
                </strong>

                ${
                  escapeHtml(
                    p.ai_reason ||
                    "No AI reasoning available."
                  )
                }

              </p>


              <div
                style="
                  margin-top:14px;
                  font-size:12px;
                  color:var(--ink-soft);
                "
              >

                <strong>
                  Department:
                </strong>

                ${escapeHtml(
                  p.department
                )}

                &nbsp; · &nbsp;

                <strong>
                  Location:
                </strong>

                ${escapeHtml(
                  p.location
                )}

              </div>

            </div>

          `).join("")}

        </div>

      </div>

    `;

    icons();


  } catch (error) {

    console.error(
      "❌ Pending placements error:",
      error
    );


    content.innerHTML = `

      <div class="card">

        <div class="state-block">

          <div class="state-icon">
            <i data-lucide="alert-circle"></i>
          </div>

          <h4>
            Unable to load placements
          </h4>

          <p>
            ${escapeHtml(
              error.message
            )}
          </p>

          <button
            class="btn btn-primary"
            onclick="renderSupervisorPlacements()"
          >
            Try Again
          </button>

        </div>

      </div>

    `;

    icons();
  }
}

async function approveSupervisorPlacement(placementId) {

  try {

    const confirmed = confirm(
      "Approve this student's placement?"
    );

    if (!confirmed) {
      return;
    }


    const res =
      await API.supervisorPlacementAction(
        placementId,
        "approve"
      );


    console.log(
      "✅ PLACEMENT APPROVAL RESPONSE:",
      res
    );


    if (!res?.success) {

      throw new Error(
        res?.message ||
        "Unable to approve placement."
      );
    }


    showToast({
      title: "Placement approved",
      msg:
        "The student's placement is now active.",
      tone: "success",
      icon: "check-circle-2"
    });


    await renderSupervisorPlacements();

  } catch (error) {

    console.error(
      "❌ Placement approval error:",
      error
    );

    showToast({
      title: "Approval failed",
      msg: error.message,
      tone: "danger",
      icon: "alert-circle"
    });
  }
}

async function rejectSupervisorPlacement(
  placementId
) {

  try {

    const reason =
      prompt(
        "Reason for returning this placement:",
        "Placement requires reassignment."
      );


    if (reason === null) {
      return;
    }


    const res =
      await API.supervisorPlacementAction(
        placementId,
        "reject",
        reason
      );


    console.log(
      "↩️ PLACEMENT REJECTION RESPONSE:",
      res
    );


    if (!res?.success) {

      throw new Error(
        res?.message ||
        "Unable to reject placement."
      );
    }


    showToast({
      title: "Placement returned",
      msg:
        "The placement has been returned for reassignment.",
      tone: "warning",
      icon: "undo-2"
    });


    await renderSupervisorPlacements();

  } catch (error) {

    console.error(
      "❌ Placement rejection error:",
      error
    );

    showToast({
      title: "Rejection failed",
      msg: error.message,
      tone: "danger",
      icon: "alert-circle"
    });
  }
}

async function renderSupervisorDashboard() {

  const el =
    document.getElementById(
      "supervisorContent"
    );

  if (!el) return;


  el.innerHTML = `
    <div class="card">
      <div class="card-pad state-block">
        <div class="spinner"></div>
        <h4>Loading dashboard...</h4>
        <p>Fetching your training overview.</p>
      </div>
    </div>
  `;


  try {

    const [
      dashboardRes,
      reportsRes,
      pendingRes
    ] = await Promise.all([

      API.getSupervisorDashboard(),

      API.getSupervisorWeeklyReports(),

      API.getPendingPlacements()

    ]);


    if (!dashboardRes?.success) {

      throw new Error(
        dashboardRes?.message ||
        "Unable to load dashboard."
      );
    }


    const stats =
      dashboardRes.statistics || {};

    const reports =
      reportsRes?.success &&
      Array.isArray(
        reportsRes.reports
      )
        ? reportsRes.reports
        : [];

    const pending =
      pendingRes?.success &&
      Array.isArray(
        pendingRes.placements
      )
        ? pendingRes.placements
        : [];


    const scores =
      reports
        .map(
          r =>
            Number(
              r.ai_performance_score
            )
        )
        .filter(
          n => Number.isFinite(n)
        );


    const averageScore =
      scores.length
        ? scores.reduce(
            (a, b) => a + b,
            0
          ) / scores.length
        : 0;


    const attention =
      reports
        .filter(
          r =>
            r.ai_performance_score !==
              null &&
            Number(
              r.ai_performance_score
            ) < 60
        )
        .slice(0, 5);


    el.innerHTML = `

      <div class="page-head">

        <div>

          <div class="eyebrow">
            SUPERVISOR DASHBOARD
          </div>

          <h1>
            Training Overview
          </h1>

          <p class="page-sub">
            Monitor your assigned students and their training progress.
          </p>

        </div>

      </div>


      <div
        class="stats-grid"
        style="margin-bottom:20px;"
      >

        <div class="stat-card">
          <div class="stat-icon">
            <i data-lucide="users"></i>
          </div>
          <div>
            <div class="stat-label">
              ASSIGNED STUDENTS
            </div>
            <div class="stat-value">
              ${Number(
                stats.total_students || 0
              )}
            </div>
          </div>
        </div>


        <div class="stat-card">
          <div class="stat-icon">
            <i data-lucide="briefcase"></i>
          </div>
          <div>
            <div class="stat-label">
              ACTIVE PLACEMENTS
            </div>
            <div class="stat-value">
              ${Number(
                stats.active_placements || 0
              )}
            </div>
          </div>
        </div>


        <div class="stat-card">
          <div class="stat-icon">
            <i data-lucide="clipboard-list"></i>
          </div>
          <div>
            <div class="stat-label">
              PENDING REPORTS
            </div>
            <div class="stat-value">
              ${Number(
                stats.pending_reports || 0
              )}
            </div>
          </div>
        </div>


        <div class="stat-card">
          <div class="stat-icon">
            <i data-lucide="sparkles"></i>
          </div>
          <div>
            <div class="stat-label">
              AVG AI SCORE
            </div>
            <div class="stat-value">
              ${
                scores.length
                  ? averageScore.toFixed(1) + "%"
                  : "—"
              }
            </div>
          </div>
        </div>

      </div>


      ${
        pending.length
          ? `
            <div
              class="card ai-card"
              style="
                margin-bottom:20px;
                cursor:pointer;
              "
              onclick="
                supervisorNav('placements')
              "
            >

              <div
                class="card-pad"
                style="
                  display:flex;
                  align-items:center;
                  gap:14px;
                "
              >

                <div class="stat-icon">
                  <i data-lucide="sparkles"></i>
                </div>

                <div style="flex:1;">

                  <strong>
                    ${pending.length}
                    pending placement
                    ${pending.length === 1 ? "" : "s"}
                  </strong>

                  <div class="cell-sub">
                    Placement requests require your attention.
                  </div>

                </div>

                <i data-lucide="arrow-right"></i>

              </div>

            </div>
          `
          : ""
      }


      <div
        class="grid"
        style="
          grid-template-columns:
            1.4fr 1fr;
          gap:18px;
        "
      >

        <div class="card">

          <div class="card-header">

            <div>
              <h3 class="card-title">
                Training Activity
              </h3>

              <p class="cell-sub">
                Weekly report activity from your students.
              </p>
            </div>

          </div>

          <div class="card-pad">

            ${
              reports.length
                ? `
                  <canvas
                    id="supSubmitChart"
                    height="120"
                  ></canvas>
                `
                : `
                  <div class="state-block">
                    <h4>No report activity</h4>
                    <p>
                      Student reports will appear here.
                    </p>
                  </div>
                `
            }

          </div>

        </div>


        <div class="card">

          <div class="card-header">

            <h3 class="card-title">
              Students Needing Attention
            </h3>

          </div>

          <div class="card-pad">

            ${
              attention.length
                ? attention.map(
                    r => `
                      <div
                        style="
                          display:flex;
                          align-items:center;
                          gap:10px;
                          padding:9px 0;
                          border-bottom:
                            1px solid var(--border-soft);
                        "
                      >

                        <div
                          class="avatar"
                          style="
                            width:32px;
                            height:32px;
                            font-size:11px;
                          "
                        >
                          ${getInitials(
                            r.full_name
                          )}
                        </div>

                        <div
                          style="flex:1;"
                        >

                          <strong
                            style="font-size:13px;"
                          >
                            ${escapeHtml(
                              r.full_name
                            )}
                          </strong>

                          <div class="cell-sub">
                            Week
                            ${Number(
                              r.week_number
                            )}
                          </div>

                        </div>

                        <span class="badge badge-danger">
                          ${Number(
                            r.ai_performance_score
                          ).toFixed(0)}%
                        </span>

                      </div>
                    `
                  ).join("")
                : `
                  <div class="state-block">
                    <div class="state-icon">
                      <i data-lucide="check-circle-2"></i>
                    </div>
                    <h4>
                      No students flagged
                    </h4>
                    <p>
                      No current AI performance concerns.
                    </p>
                  </div>
                `
            }

          </div>

        </div>

      </div>


      <div
        class="card"
        style="margin-top:18px;"
      >

        <div class="card-header">

          <h3 class="card-title">
            Report Summary
          </h3>

        </div>

        <div
          class="card-pad"
          style="
            display:grid;
            grid-template-columns:
              repeat(3, minmax(0,1fr));
            gap:14px;
          "
        >

          <div>
            <div class="stat-label">
              APPROVED
            </div>
            <div class="stat-value">
              ${Number(
                stats.approved_reports || 0
              )}
            </div>
          </div>

          <div>
            <div class="stat-label">
              PENDING
            </div>
            <div class="stat-value">
              ${Number(
                stats.pending_reports || 0
              )}
            </div>
          </div>

          <div>
            <div class="stat-label">
              REJECTED
            </div>
            <div class="stat-value">
              ${Number(
                stats.rejected_reports || 0
              )}
            </div>
          </div>

        </div>

      </div>

    `;


    icons();


    if (
      reports.length &&
      document.getElementById(
        "supSubmitChart"
      )
    ) {

      const grouped = {};

      reports.forEach(r => {

        const week =
          Number(r.week_number);

        if (!grouped[week]) {

          grouped[week] = {
            approved: 0,
            pending: 0,
            rejected: 0
          };

        }

        const status =
          String(
            r.status || ""
          ).toLowerCase();

        if (status === "approved") {
          grouped[week].approved++;
        }
        else if (status === "rejected") {
          grouped[week].rejected++;
        }
        else {
          grouped[week].pending++;
        }

      });


      const weeks =
        Object.keys(grouped)
          .map(Number)
          .sort(
            (a, b) => a - b
          );


      new Chart(
        document.getElementById(
          "supSubmitChart"
        ),
        {
          type: "bar",

          data: {

            labels:
              weeks.map(
                w => "W" + w
              ),

            datasets: [

              {
                label: "Approved",
                data:
                  weeks.map(
                    w =>
                      grouped[w].approved
                  )
              },

              {
                label: "Pending",
                data:
                  weeks.map(
                    w =>
                      grouped[w].pending
                  )
              },

              {
                label: "Rejected",
                data:
                  weeks.map(
                    w =>
                      grouped[w].rejected
                  )
              }

            ]

          },

          options: chartOpts()

        }
      );
    }

  } catch (error) {

    console.error(
      "❌ Supervisor dashboard error:",
      error
    );

    el.innerHTML = `
      <div class="card">
        <div class="state-block">
          <div class="state-icon">
            <i data-lucide="alert-circle"></i>
          </div>

          <h4>
            Unable to load dashboard
          </h4>

          <p>
            ${escapeHtml(error.message)}
          </p>

          <button
            class="btn btn-primary"
            onclick="
              renderSupervisorDashboard()
            "
          >
            Try Again
          </button>
        </div>
      </div>
    `;

    icons();
  }
}

async function renderSupervisorStudents() {

  const content =
    document.getElementById(
      "supervisorContent"
    );

  content.innerHTML = `
    <div class="card">
      <div class="state-block">
        <div class="spinner"></div>
        <h4>Loading students...</h4>
      </div>
    </div>
  `;

  try {

    const res =
      await API.getSupervisorWeeklyReports();

    if (!res?.success) {
      throw new Error(
        res?.message ||
        "Unable to load students."
      );
    }

    const reports =
      Array.isArray(res.reports)
        ? res.reports
        : [];

    const students = {};

    reports.forEach(report => {

      const id =
        Number(report.student_id);

      if (!id) return;

      if (!students[id]) {

        students[id] = {
          name: report.full_name,
          matric_no: report.matric_no,
          organization:
            report.organization_name,
          status:
            report.placement_status,
          reports: 0,
          score: null
        };

      }

      students[id].reports++;

      if (
        report.ai_performance_score !==
        null &&
        report.ai_performance_score !==
        undefined
      ) {

        students[id].score =
          Number(
            report.ai_performance_score
          );

      }

    });


    const list =
      Object.values(students);


    if (!list.length) {

      content.innerHTML = `
        <div class="card">
          <div class="state-block">
            <div class="state-icon">
              <i data-lucide="users"></i>
            </div>
            <h4>No students assigned</h4>
            <p>
              Assigned students will appear here.
            </p>
          </div>
        </div>
      `;

      icons();
      return;
    }


    content.innerHTML = `

      <div class="card">

        <div class="card-header">

          <h3 class="card-title">
            My Students
          </h3>

          <span class="badge badge-info">
            ${list.length}
          </span>

        </div>


        <div class="table-wrap">

          <table class="data-table">

            <thead>
              <tr>
                <th>Student</th>
                <th>Matric No.</th>
                <th>Organization</th>
                <th>Reports</th>
                <th>AI Score</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>

              ${list.map(student => `

                <tr>

                  <td data-label="Student">
                    <strong>
                      ${escapeHtml(student.name)}
                    </strong>
                  </td>

                  <td data-label="Matric No.">
                    ${escapeHtml(
                      student.matric_no || "—"
                    )}
                  </td>

                  <td data-label="Organization">
                    ${escapeHtml(
                      student.organization || "—"
                    )}
                  </td>

                  <td data-label="Reports">
                    ${student.reports}
                  </td>

                  <td data-label="AI Score">
                    ${
                      student.score !== null
                        ? student.score.toFixed(1) + "%"
                        : "—"
                    }
                  </td>

                  <td data-label="Status">
                    <span class="badge badge-success">
                      ${escapeHtml(
                        student.status || "active"
                      )}
                    </span>
                  </td>

                </tr>

              `).join("")}

            </tbody>

          </table>

        </div>

      </div>
    `;

    icons();

  } catch (error) {

    console.error(
      "❌ My Students error:",
      error
    );

    content.innerHTML = `
      <div class="card">
        <div class="state-block">

          <h4>
            Unable to load students
          </h4>

          <p>
            ${escapeHtml(error.message)}
          </p>

        </div>
      </div>
    `;

    icons();
  }
}


function studentStatusBadge(status){
  const map = {'On Track':'badge-success','Needs Attention':'badge-warning','At Risk':'badge-danger'};
  return `<span class="badge ${map[status]}">${status}</span>`;
}

function openStudentProfileModal(i){
  const s = DB.supervisorStudents[i];
  openModal(`<div class="modal-head"><h3 class="card-title">${s.name}</h3><button class="icon-btn" onclick="closeModal()"><i data-lucide="x"></i></button></div>
  <div class="modal-body">
    <div class="grid grid-2">
      ${profField('Programme', s.programme)} ${profField('Organization', s.org)}
      ${profField('Training week', s.week+' / 10')} ${profField('Attendance', s.attendance+'%')}
      ${profField('AI score', s.ai+'%')} ${profField('Status', s.status)}
    </div>
  </div>
  <div class="modal-foot"><button class="btn btn-outline" onclick="closeModal()">Close</button><button class="btn btn-primary" onclick="closeModal();supervisorNav('reports')">Review reports</button></div>`);
}

async function renderSupervisorReports() {

  const content =
    document.getElementById(
      "supervisorContent"
    );

  if (!content) return;


  content.innerHTML = `
    <div class="card">
      <div class="state-block">
        <div class="spinner"></div>

        <h4>
          Loading weekly reports...
        </h4>

        <p>
          Fetching reports submitted by your students.
        </p>
      </div>
    </div>
  `;


  try {

    const res =
      await API.getSupervisorWeeklyReports();

    console.log(
      "📚 SUPERVISOR WEEKLY REPORTS:",
      res
    );


    if (!res?.success) {

      throw new Error(
        res?.message ||
        "Unable to load weekly reports."
      );
    }


    const reports =
      Array.isArray(res.reports)
        ? res.reports
        : [];


    window.supervisorReports =
      reports;


    renderSupervisorReportList(
      reports,
      "pending"
    );


  } catch (error) {

    console.error(
      "❌ Supervisor reports error:",
      error
    );


    content.innerHTML = `
      <div class="card">
        <div class="state-block">

          <div class="state-icon">
            <i data-lucide="alert-circle"></i>
          </div>

          <h4>
            Unable to load weekly reports
          </h4>

          <p>
            ${escapeHtml(
              error.message
            )}
          </p>

          <button
            class="btn btn-primary"
            onclick="renderSupervisorReports()"
          >
            Try Again
          </button>

        </div>
      </div>
    `;

    icons();
  }
}

function renderSupervisorReportList(
  reports,
  mode = "pending"
) {

  const list =
    document.getElementById(
      "supReportsList"
    );

  if (!list) {

    document.getElementById(
      "supervisorContent"
    ).innerHTML = `

      <div class="tabs">

        <div
          class="tab active"
          onclick="switchSupervisorReportTab(this,'pending')"
        >
          Pending review
        </div>

        <div
          class="tab"
          onclick="switchSupervisorReportTab(this,'all')"
        >
          All reports
        </div>

      </div>

      <div id="supReportsList"></div>

    `;

    return renderSupervisorReportList(
      reports,
      mode
    );
  }


  const rows =
    mode === "pending"
      ? reports.filter(
          r =>
            String(r.status).toLowerCase() ===
            "pending"
        )
      : reports;


  list.innerHTML =
    rows.length
      ? `

        <div class="card">

          <div class="table-wrap">

            <table class="data-table">

              <thead>

                <tr>
                  <th>Student</th>
                  <th>Week</th>
                  <th>Submitted</th>
                  <th>Status</th>
                  <th></th>
                </tr>

              </thead>

              <tbody>

                ${rows.map(report => `

                  <tr>

                    <td data-label="Student">

                      <div
                        style="
                          display:flex;
                          align-items:center;
                          gap:9px;
                        "
                      >

                        <div
                          class="avatar"
                          style="
                            width:28px;
                            height:28px;
                            font-size:10px;
                          "
                        >
                          ${escapeHtml(
                            getInitials(
                              report.full_name
                            )
                          )}
                        </div>

                        <div>

                          <div>
                            ${escapeHtml(
                              report.full_name
                            )}
                          </div>

                          <div class="cell-sub">
                            ${escapeHtml(
                              report.matric_no ||
                              ""
                            )}
                          </div>

                        </div>

                      </div>

                    </td>


                    <td data-label="Week">
                      Week ${Number(
                        report.week_number
                      )}
                    </td>


                    <td data-label="Submitted">

                      ${
                        report.submitted_at
                          ? formatDate(
                              report.submitted_at
                            )
                          : "—"
                      }

                    </td>


                    <td data-label="Status">
                      ${statusBadgeForReport(
                        report.status
                      )}
                    </td>


                    <td>

                      <button
                        class="btn btn-primary btn-sm"
                        onclick="openSupervisorReportReview(${Number(report.report_id || report.id)})"
                      >
                        Review
                      </button>

                    </td>

                  </tr>

                `).join("")}

              </tbody>

            </table>

          </div>

        </div>

      `
      : `

        <div class="card">

          <div class="state-block">

            <div class="state-icon">
              <i data-lucide="check-check"></i>
            </div>

            <h4>
              ${
                mode === "pending"
                  ? "All caught up"
                  : "No reports found"
              }
            </h4>

            <p>
              ${
                mode === "pending"
                  ? "No reports are waiting for review."
                  : "No weekly reports are available."
              }
            </p>

          </div>

        </div>

      `;


  icons();
}

function switchSupervisorReportTab(
  el,
  mode
) {

  document
    .querySelectorAll(
      "#supervisorContent .tab"
    )
    .forEach(
      tab =>
        tab.classList.remove("active")
    );

  el.classList.add("active");

  renderSupervisorReportList(
    window.supervisorReports || [],
    mode
  );
}

function getInitials(name = "") {

  return name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map(
      part =>
        part.charAt(0).toUpperCase()
    )
    .join("");
}

function formatDate(value) {

  if (!value) return "—";

  const date =
    new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return date.toLocaleDateString(
    undefined,
    {
      day: "2-digit",
      month: "short",
      year: "numeric"
    }
  );
}

function supRptTab(el,mode){ document.querySelectorAll('#supervisorContent .tab').forEach(t=>t.classList.remove('active')); el.classList.add('active'); renderSupReportsList(mode); }
function renderSupReportsList(mode){
  const rows = mode==='pending' ? DB.weeklyReports.filter(r=>r.status==='Pending Review') : DB.weeklyReports;
  document.getElementById('supReportsList').innerHTML = rows.length ? `<div class="card"><div class="table-wrap"><table class="data-table">
      <thead><tr><th>Student</th><th>Week</th><th>Hours</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
      <tbody>${rows.map(r=>`<tr>
        <td data-label="Student"><div style="display:flex;align-items:center;gap:9px;"><div class="avatar" style="width:28px;height:28px;font-size:10.5px;">DO</div>Daniel Okafor</div></td>
        <td data-label="Week">Week ${r.week}</td><td data-label="Hours">${r.hours}h</td><td data-label="Submitted">${r.range.split('–')[1]||r.range}</td>
        <td data-label="Status">${statusBadgeForReport(r.status)}</td>
        <td data-label=""><button class="btn btn-primary btn-sm" onclick="openReportReview(${r.week})">Review</button></td>
      </tr>`).join('')}</tbody></table></div></div>`
    : `<div class="card"><div class="state-block"><div class="state-icon"><i data-lucide="check-check"></i></div><h4>All caught up</h4><p>No reports waiting for review right now.</p></div></div>`;
  icons();
}
async function openSupervisorReportReview(
  reportId
) {

  try {

    const res =
      await API.getSupervisorWeeklyReport(
        reportId
      );


    console.log(
      "📄 SUPERVISOR REPORT:",
      res
    );


    if (!res?.success) {

      throw new Error(
        res?.message ||
        "Unable to load report."
      );
    }


    const report =
      res.report;


    const aiHtml =
      report.ai_analysis_id
        ? `
          <div
            class="card ai-card"
            style="
              padding:16px;
              margin-top:12px;
            "
          >

            <span class="ai-badge">
              <i data-lucide="sparkles"></i>
              AI Analysis
            </span>


            <div
              style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:16px;
                margin-top:12px;
              "
            >

              <div>

                <div
                  style="
                    font-size:12px;
                    color:var(--ink-soft);
                  "
                >
                  Performance score
                </div>

                <div
                  style="
                    font-size:32px;
                    font-weight:700;
                  "
                >
                  ${Number(
                    report.ai_performance_score || 0
                  ).toFixed(0)}%
                </div>

              </div>

            </div>


            ${profField(
              "AI Summary",
              report.ai_summary || "—"
            )}

            ${profField(
              "Skills Identified",
              report.ai_skills_identified || "—"
            )}

            ${profField(
              "Strengths",
              report.ai_strengths || "—"
            )}

            ${profField(
              "Areas for Improvement",
              report.ai_weaknesses || "—"
            )}

            ${profField(
              "Recommendations",
              report.ai_recommendations || "—"
            )}

          </div>
        `
        : `
          <div
            class="card"
            style="
              margin-top:12px;
            "
          >

            <div
              style="
                padding:16px;
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:14px;
              "
            >

              <div>

                <strong>
                  AI Analysis
                </strong>

                <div
                  style="
                    font-size:12px;
                    color:var(--ink-soft);
                    margin-top:4px;
                  "
                >
                  This report has not been analysed yet.
                </div>

              </div>


              <button
                class="btn btn-primary btn-sm"
                onclick="
                  runSupervisorReportAI(
                    this,
                    ${Number(
                      report.id ||
                      report.report_id
                    )}
                  )
                "
              >

                <i
                  data-lucide="sparkles"
                  style="
                    width:14px;
                    height:14px;
                  "
                ></i>

                Analyze with AI

              </button>

            </div>

          </div>
        `;


    openModal(`

      <div class="modal-head">

        <h3 class="card-title">

          Week ${Number(
            report.week_number
          )}
          report —
          ${escapeHtml(
            report.full_name
          )}

        </h3>

        <button
          class="icon-btn"
          onclick="closeModal()"
        >
          <i data-lucide="x"></i>
        </button>

      </div>


      <div class="modal-body">

        <div
          class="grid grid-2"
          style="margin-bottom:6px;"
        >

          ${profField(
            "Student",
            report.full_name
          )}

          ${profField(
            "Matric No.",
            report.matric_no
          )}

          ${profField(
            "Department",
            report.department
          )}

          ${profField(
            "Programme",
            report.programme
          )}

        </div>


        ${profField(
          "Activities performed",
          report.activities
        )}


        <div class="grid grid-2">

          ${profField(
            "Skills learned",
            report.skills_learned || "—"
          )}

          ${profField(
            "Challenges",
            report.challenges || "—"
          )}

        </div>


        ${profField(
          "Student comment",
          report.student_comment || "—"
        )}


        ${profField(
          "Submitted",
          formatDate(report.submitted_at)
        )}


        ${aiHtml}


        <div
          class="field"
          style="margin-top:14px;"
        >

          <label>
            Supervisor feedback
          </label>

          <textarea
            class="input"
            id="supervisorReportFeedback"
            placeholder="Write feedback for the student..."
          >${escapeHtml(
            report.supervisor_comment || ""
          )}</textarea>

        </div>

      </div>


      <div class="modal-foot">

        ${
          String(report.status).toLowerCase() ===
          "pending"

            ? `

              <button
                class="btn btn-outline btn-danger"
                onclick="rejectSupervisorReport(${Number(report.id || report.report_id)})"
              >
                Request Correction
              </button>

              <button
                class="btn btn-success"
                onclick="approveSupervisorReport(${Number(report.id || report.report_id)})"
              >
                <i
                  data-lucide="check"
                  style="
                    width:14px;
                    height:14px;
                  "
                ></i>
                Approve Report
              </button>

            `

            : `

              <button
                class="btn btn-outline"
                onclick="closeModal()"
              >
                Close
              </button>

            `
        }

      </div>

    `, "modal-lg");


    icons();


  } catch (error) {

    console.error(
      "❌ Report view error:",
      error
    );

    showToast({
      title: "Unable to open report",
      msg: error.message,
      tone: "danger",
      icon: "alert-circle"
    });
  }
}

async function approveSupervisorReport(
  reportId
) {

  const comment =
    document.getElementById(
      "supervisorReportFeedback"
    )?.value.trim() || "";


  try {

    const res =
      await API.reviewSupervisorWeeklyReport(
        reportId,
        "approve",
        comment
      );


    console.log(
      "✅ REPORT APPROVAL RESPONSE:",
      res
    );


    if (!res?.success) {

      throw new Error(
        res?.message ||
        "Unable to approve report."
      );
    }


    closeModal();


    showToast({
      title: "Report approved",
      msg:
        "The weekly report has been approved.",
      tone: "success",
      icon: "check-circle-2"
    });


    await renderSupervisorReports();


  } catch (error) {

    console.error(
      "❌ Report approval error:",
      error
    );

    showToast({
      title: "Approval failed",
      msg: error.message,
      tone: "danger",
      icon: "alert-circle"
    });
  }
}

async function rejectSupervisorReport(
  reportId
) {

  const comment =
    document.getElementById(
      "supervisorReportFeedback"
    )?.value.trim() || "";


  if (!comment) {

    showToast({
      title: "Feedback required",
      msg:
        "Please explain what the student should correct.",
      tone: "warning",
      icon: "message-square"
    });

    return;
  }


  try {

    const res =
      await API.reviewSupervisorWeeklyReport(
        reportId,
        "reject",
        comment
      );


    console.log(
      "↩️ REPORT REJECTION RESPONSE:",
      res
    );


    if (!res?.success) {

      throw new Error(
        res?.message ||
        "Unable to reject report."
      );
    }


    closeModal();


    showToast({
      title: "Correction requested",
      msg:
        "The report has been returned to the student.",
      tone: "warning",
      icon: "undo-2"
    });


    await renderSupervisorReports();


  } catch (error) {

    console.error(
      "❌ Report rejection error:",
      error
    );

    showToast({
      title: "Rejection failed",
      msg: error.message,
      tone: "danger",
      icon: "alert-circle"
    });
  }
}

async function renderSupervisorEvaluations() {

  const content =
    document.getElementById("supervisorContent");

  content.innerHTML = `
    <div class="card">
      <div class="state-block">
        <div class="spinner"></div>
        <h4>Loading eligible students...</h4>
        <p>Checking completed training records.</p>
      </div>
    </div>
  `;

  try {

    const res =
      await API.getSupervisorWeeklyReports();

    if (!res?.success) {
      throw new Error(
        res?.message ||
        "Unable to load students."
      );
    }

    const reports =
      Array.isArray(res.reports)
        ? res.reports
        : [];

    const grouped = {};

    reports.forEach(report => {

      const id =
        Number(report.student_id);

      if (!id) return;

      if (!grouped[id]) {
        grouped[id] = {
          placement_id:
            Number(report.placement_id),

          student_id:
            id,

          full_name:
            report.full_name,

          matric_no:
            report.matric_no,

          department:
            report.department,

          programme:
            report.programme,

          start_date:
            report.start_date,

          end_date:
            report.end_date,

          placement_status:
            report.placement_status,

          report_count:
            0,

          approved_count:
            0
        };
      }

      grouped[id].report_count++;

      if (
        String(report.status).toLowerCase() ===
        "approved"
      ) {
        grouped[id].approved_count++;
      }

    });

    const students =
      Object.values(grouped);


    /*
    |--------------------------------------------------------------------------
    | Only allow final evaluation when training is completed
    |--------------------------------------------------------------------------
    */

    const today =
      new Date();

    const eligibleStudents =
      students.filter(student => {

        const endDate =
          student.end_date
            ? new Date(
                student.end_date + "T23:59:59"
              )
            : null;

        const completedByDate =
          endDate &&
          endDate <= today;

        const completedByStatus =
          String(
            student.placement_status || ""
          ).toLowerCase() ===
          "completed";

        return (
          completedByDate ||
          completedByStatus
        );
      });


    if (!eligibleStudents.length) {

      content.innerHTML = `
        <div class="card">

          <div class="state-block">

            <div class="state-icon">
              <i data-lucide="clipboard-check"></i>
            </div>

            <h4>
              No final evaluations available
            </h4>

            <p>
              Final evaluation becomes available
              after a student's training period is completed.
            </p>

          </div>

        </div>
      `;

      icons();
      return;
    }


    content.innerHTML = `

      <div class="card">

        <div class="card-header">

          <div>

            <h3 class="card-title">
              Final Evaluations
            </h3>

            <p class="cell-sub">
              Evaluate students who have completed their training.
            </p>

          </div>

        </div>


        <div class="card-pad">

          ${eligibleStudents.map(student => `

            <div
              class="card"
              style="
                margin-bottom:10px;
                border:1px solid var(--border);
              "
            >

              <div
                style="
                  display:flex;
                  justify-content:space-between;
                  align-items:center;
                  gap:16px;
                "
              >

                <div>

                  <strong>
                    ${escapeHtml(
                      student.full_name
                    )}
                  </strong>

                  <div class="cell-sub">
                    ${escapeHtml(
                      student.matric_no || ""
                    )}
                  </div>

                  <div class="cell-sub">
                    ${escapeHtml(
                      student.programme || ""
                    )}
                  </div>

                  <div class="cell-sub">
                    Training:
                    ${formatDate(student.start_date)}
                    →
                    ${formatDate(student.end_date)}
                  </div>

                </div>


                <button
                  class="btn btn-primary btn-sm"
                  onclick="
                    openSupervisorEvaluation(
                      ${student.placement_id}
                    )
                  "
                >
                  Evaluate
                </button>

              </div>

            </div>

          `).join("")}

        </div>

      </div>

    `;

    icons();

  } catch (error) {

    console.error(
      "❌ Evaluation loading error:",
      error
    );

    content.innerHTML = `
      <div class="card">

        <div class="state-block">

          <div class="state-icon">
            <i data-lucide="alert-circle"></i>
          </div>

          <h4>
            Unable to load evaluations
          </h4>

          <p>
            ${escapeHtml(error.message)}
          </p>

          <button
            class="btn btn-primary"
            onclick="renderSupervisorEvaluations()"
          >
            Try Again
          </button>

        </div>

      </div>
    `;

    icons();
  }
}

async function openSupervisorEvaluation(
  placementId
) {

  openModal(`

    <div class="modal-head">

      <h3 class="card-title">
        Final Evaluation
      </h3>

      <button
        class="icon-btn"
        onclick="closeModal()"
      >
        <i data-lucide="x"></i>
      </button>

    </div>


    <div class="modal-body">

      <p class="cell-sub">
        Give your final assessment of the student's
        industrial training performance.
      </p>


      <div class="field">

        <label>
          Supervisor Score (0–100)
        </label>

        <input
          type="number"
          class="input"
          id="finalSupervisorScore"
          min="0"
          max="100"
          step="0.01"
          placeholder="Enter final score"
        >

      </div>

    </div>


    <div class="modal-foot">

      <button
        class="btn btn-outline"
        onclick="closeModal()"
      >
        Cancel
      </button>

      <button
        class="btn btn-primary"
        onclick="
          submitSupervisorEval(
            ${Number(placementId)}
          )
        "
      >
        Submit Evaluation
      </button>

    </div>

  `, "modal-md");

  icons();
}

async function submitSupervisorEval(
  placementId
) {

  const input =
    document.getElementById(
      "finalSupervisorScore"
    );

  const score =
    Number(input?.value);


  if (
    !Number.isFinite(score) ||
    score < 0 ||
    score > 100
  ) {

    showToast({
      title: "Invalid score",
      msg:
        "Enter a score between 0 and 100.",
      tone: "warning",
      icon: "alert-circle"
    });

    return;
  }


  try {

  const res =
    await API.submitSupervisorEvaluation({
      placement_id: Number(placementId),
      supervisor_score: score
    });


    console.log(
      "✅ FINAL EVALUATION RESPONSE:",
      res
    );


    if (!res?.success) {

      if (
        res?.completed_weeks !== undefined
      ) {

        throw new Error(
          res.message ||
          `Final evaluation is not available yet. ` +
          `The student has completed ` +
          `${res.completed_weeks} of ` +
          `${res.required_weeks} required weekly reports.`
        );

      }


      throw new Error(
        res?.message ||
        "Unable to submit final evaluation."
      );
    }


    closeModal();


    showToast({
      title: "Evaluation submitted",
      msg:
        "Supervisor final evaluation has been saved.",
      tone: "success",
      icon: "check-circle-2"
    });


    /*
    |--------------------------------------------------------------------------
    | GENERATE AI FINAL EVALUATION
    |--------------------------------------------------------------------------
    */

    await generateSupervisorAIFinalEvaluation(
      Number(placementId)
    );


  } catch (error) {

    console.error(
      "❌ Supervisor evaluation error:",
      error
    );


    showToast({
      title: "Evaluation failed",
      msg:
        error?.message ||
        "Unable to submit final evaluation.",
      tone: "danger",
      icon: "alert-circle"
    });

  }

}

async function generateSupervisorAIFinalEvaluation(
  placementId
) {

  try {

    showToast({
      title: "AI evaluation started",
      msg:
        "Analyzing the student's complete SIWES history...",
      tone: "ai",
      icon: "sparkles"
    });


    const res =
      await API.generateAIFinalEvaluation(
        Number(placementId)
      );


    console.log(
      "🤖 AI FINAL EVALUATION RESPONSE:",
      res
    );


    if (!res?.success) {

      throw new Error(
        res?.message ||
        "Unable to generate AI final evaluation."
      );
    }


    showToast({
      title: "AI evaluation completed",
      msg:
        "The student's final AI evaluation has been generated.",
      tone: "ai",
      icon: "sparkles"
    });


    supervisorNav(
      "evaluations"
    );


  } catch (error) {

    console.error(
      "❌ AI final evaluation error:",
      error
    );


    showToast({
      title: "AI evaluation failed",
      msg:
        error?.message ||
        "Unable to generate AI final evaluation.",
      tone: "danger",
      icon: "alert-circle"
    });
  }
}

function ratingRow(cat){
  return `<div class="field"><label>${cat}</label><div style="display:flex;gap:6px;">${[1,2,3,4,5].map(n=>`<button type="button" class="btn-icon btn-outline" style="width:34px;height:34px;" onclick="setRating(this,${n})"><i data-lucide="star" style="width:15px;height:15px;"></i></button>`).join('')}</div></div>`;
}

function setRating(el, n){
  const row = el.parentElement;
  Array.from(row.children).forEach((btn,i)=>{ btn.style.background = i<n?'var(--accent-tint)':''; btn.style.borderColor = i<n?'var(--accent)':''; btn.querySelector('i').style.color = i<n?'var(--accent-600)':''; });
}

async function renderSupervisorAIInsights() {

  const content =
    document.getElementById(
      "supervisorContent"
    );

  content.innerHTML = `
    <div class="card">
      <div class="state-block">
        <div class="spinner"></div>
        <h4>Loading AI insights...</h4>
      </div>
    </div>
  `;

  try {

    const res =
      await API.getSupervisorWeeklyReports();

    if (!res?.success) {
      throw new Error(
        res?.message ||
        "Unable to load AI insights."
      );
    }

    const reports =
      Array.isArray(res.reports)
        ? res.reports
        : [];

    const analyzed =
      reports.filter(
        r =>
          r.ai_performance_score !== null &&
          r.ai_performance_score !== undefined
      );


    const scores =
      analyzed.map(
        r =>
          Number(
            r.ai_performance_score
          )
      );


    const average =
      scores.length
        ? scores.reduce(
            (a, b) => a + b,
            0
          ) / scores.length
        : 0;


    const needsAttention =
      analyzed.filter(
        r =>
          Number(
            r.ai_performance_score
          ) < 60
      ).length;


    const students =
      new Set(
        analyzed.map(
          r => Number(r.student_id)
        )
      );


    content.innerHTML = `

      <div class="grid grid-3">

        ${statCard(
          "Students analyzed",
          students.size,
          "users"
        )}

        ${statCard(
          "Average AI Score",
          average.toFixed(1) + "%",
          "sparkles"
        )}

        ${statCard(
          "Needs Attention",
          needsAttention,
          "alert-triangle"
        )}

      </div>


      <div
        class="card ai-card"
        style="margin-top:18px;"
      >

        <div class="card-header">

          <span class="ai-badge">
            <i data-lucide="brain-circuit"></i>
            Team AI Performance
          </span>

        </div>

        <div class="card-pad">

          ${
            analyzed.length
              ? `<canvas
                   id="supervisorAIChart"
                   height="110"
                 ></canvas>`
              : `
                <div class="state-block">
                  <h4>No AI analyses yet</h4>
                  <p>
                    AI results will appear after reports are analyzed.
                  </p>
                </div>
              `
          }

        </div>

      </div>


      <div
        class="card"
        style="margin-top:18px;"
      >

        <div class="card-header">

          <h3 class="card-title">
            Recent AI Results
          </h3>

        </div>

        <div class="card-pad">

          ${
            analyzed.length
              ? analyzed
                  .slice(0, 10)
                  .map(
                    r => `

                      <div
                        style="
                          display:flex;
                          justify-content:space-between;
                          align-items:center;
                          padding:10px 0;
                          border-bottom:1px solid var(--border);
                        "
                      >

                        <div>

                          <strong>
                            ${escapeHtml(
                              r.full_name
                            )}
                          </strong>

                          <div class="cell-sub">
                            Week ${Number(
                              r.week_number
                            )}
                          </div>

                        </div>

                        <strong>
                          ${Number(
                            r.ai_performance_score
                          ).toFixed(1)}%
                        </strong>

                      </div>
                    `
                  )
                  .join("")
              : `
                <div class="state-block">
                  <h4>No results available</h4>
                </div>
              `
          }

        </div>

      </div>

    `;

    icons();


    if (
      analyzed.length &&
      document.getElementById(
        "supervisorAIChart"
      )
    ) {

      new Chart(
        document.getElementById(
          "supervisorAIChart"
        ),
        {

          type: "bar",

          data: {

            labels:
              analyzed.map(
                r =>
                  r.full_name.split(" ")[0] +
                  " W" +
                  r.week_number
              ),

            datasets: [

              {
                label: "AI Score",
                data: scores
              }

            ]

          },

          options: chartOpts()

        }
      );
    }

  } catch (error) {

    console.error(
      "❌ Supervisor AI insights error:",
      error
    );

    content.innerHTML = `
      <div class="card">
        <div class="state-block">

          <h4>
            Unable to load AI insights
          </h4>

          <p>
            ${escapeHtml(error.message)}
          </p>

        </div>
      </div>
    `;

    icons();
  }
}

function renderSupervisorMessages(){
  document.getElementById('supervisorContent').innerHTML = `
    <div class="card"><div class="state-block">
      <div class="state-icon"><i data-lucide="message-square"></i></div>
      <h4>No open conversations</h4><p>Messages from your assigned students will appear here.</p>
      <button class="btn btn-primary" onclick="showToast({title:'Compose message', msg:'Message composer would open here.', tone:'info', icon:'message-square'})">Start a conversation</button>
    </div></div>`;
  icons();
}
async function renderSupervisorProfile() {

  const content =
    document.getElementById("supervisorContent");

  content.innerHTML = `
    <div class="card">
      <div class="state-block">
        <div class="spinner"></div>
        <h4>Loading profile...</h4>
      </div>
    </div>
  `;

  try {

    const res =
      await API.getSupervisorDashboard();

    if (!res?.success) {
      throw new Error(
        res?.message ||
        "Unable to load supervisor profile."
      );
    }

    const supervisor =
      res.supervisor || {};

    content.innerHTML = `

      <div class="card">

        <div class="card-header">

          <div>
            <h3 class="card-title">
              Supervisor Profile
            </h3>

            <p class="cell-sub">
              Your supervisor account information.
            </p>
          </div>

        </div>

        <div class="card-pad">

          <div
            style="
              display:flex;
              align-items:center;
              gap:16px;
              margin-bottom:20px;
            "
          >

            <div
              class="avatar"
              style="
                width:64px;
                height:64px;
                font-size:20px;
              "
            >
              ${getInitials(
                supervisor.full_name ||
                "Supervisor"
              )}
            </div>

            <div>

              <h3 style="margin:0;">
                ${escapeHtml(
                  supervisor.full_name ||
                  "Supervisor"
                )}
              </h3>

              <div class="cell-sub">
                Supervisor
              </div>

            </div>

          </div>

          ${profField(
            "Full Name",
            supervisor.full_name || "—"
          )}

          ${profField(
            "Supervisor ID",
            supervisor.id || "—"
          )}

        </div>

      </div>

    `;

    icons();

  } catch (error) {

    console.error(
      "❌ Supervisor profile error:",
      error
    );

    content.innerHTML = `
      <div class="card">
        <div class="state-block">

          <div class="state-icon">
            <i data-lucide="alert-circle"></i>
          </div>

          <h4>
            Unable to load profile
          </h4>

          <p>
            ${escapeHtml(
              error.message
            )}
          </p>

        </div>
      </div>
    `;

    icons();
  }
}

function restoreLoginState() {

  try {

    const savedUser =
      localStorage.getItem("AI-ITMS_user");

    const savedStudent =
      localStorage.getItem("AI-ITMS_student");

    const savedRole =
      localStorage.getItem("AI-ITMS_role");

    console.log("🔄 Checking saved login...");

    if (!savedUser || !savedRole) {
      console.log("ℹ️ No saved login found.");
      return;
    }

    state.currentUser =
      JSON.parse(savedUser);

    state.currentRole =
      savedRole;

    if (savedStudent) {
      state.currentStudent =
        JSON.parse(savedStudent);
    }

    console.log(
      "✅ Restoring session:",
      state.currentRole,
      state.currentUser
    );

    enterPortal(state.currentRole);

  } catch (error) {

    console.error(
      "❌ Session restore failed:",
      error
    );

    localStorage.removeItem("AI-ITMS_user");
    localStorage.removeItem("AI-ITMS_student");
    localStorage.removeItem("AI-ITMS_role");
  }
}

/* =====================================================================
   BOOTSTRAP
   ===================================================================== */
document.addEventListener(
  "DOMContentLoaded",
  () => {

    icons();

    restoreLoginState();

    updateNotificationBadge();

  }
);