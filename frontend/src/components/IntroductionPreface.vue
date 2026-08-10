<script setup lang="ts">
defineProps<{
  currentPage: number;
}>();

defineEmits<{
  (e: 'goToChapter', chapterNum: string): void;
}>();

const authors = [
  "Shanko Chura Aredo (Ph.D)",
  "Hailu Jelde Wedajo (MA)",
  "Chala Seya Yadola (MA)",
  "Kedir Elka Amigna (MSc.)",
  "Deyas Deme Merga (MSc.)",
  "Fikadu Nigussie Haile (BSc.)"
];

const toc = [
  { chapter: "1", title: "Introduction", page: "1" },
  { chapter: "2", title: "Smart Governance", page: "26" },
  { chapter: "3", title: "Digital Adama", page: "65" },
  { chapter: "4", title: "Smart Security", page: "76" },
  { chapter: "5", title: "Smart Urban Design and Land Use Management", page: "212" },
  { chapter: "6", title: "Smart Environment and Organic Production", page: "235" },
  { chapter: "7", title: "Smart Mobility", page: "271" },
  { chapter: "8", title: "Smart Social Services", page: "348" },
  { chapter: "9", title: "Smart Tourism and Culture", page: "362" },
  { chapter: "10", title: "Smart Public Relation, Research and Knowledge Management", page: "395" },
  { chapter: "11", title: "Smart People", page: "404" },
];

const acronyms = [
  { term: "2K", desc: "2048-pixel display" },
  { term: "3GPP", desc: "Third Generation Partnership Project" },
  { term: "4K", desc: "4096-pixel display" },
  { term: "8K", desc: "8192-Pixel display" },
  { term: "AES", desc: "Advanced Encryption Standard" },
  { term: "AI", desc: "Artificial Intelligence" },
  { term: "BOD", desc: "Biological Oxygen Demand" },
  { term: "BRT", desc: "Bus Rapid Transit" },
  { term: "CCTV", desc: "Closed-Circuit Television" },
  { term: "DDoS", desc: "Distributed Denial of Service" },
  { term: "GIS", desc: "Geographic Information Systems" },
  { term: "GPS", desc: "Global Positioning System" },
  { term: "IoT", desc: "Internet of Things" },
  { term: "LED", desc: "Light Emitting Diode" },
  { term: "LTE", desc: "Long Term Evolution" },
  { term: "M2M", desc: "Machine to Machine" },
  { term: "WiFi", desc: "Wireless Fidelity" }
];
</script>

<template>
  <div class="w-full text-(--rt-text-body) animate-fade" :key="currentPage">
    
    <!-- ================= PAGE 1 ================= -->
    <div v-if="currentPage === 1" class="space-y-16 pb-8">
      <section class="text-center space-y-8 pb-12 border-b border-(--rt-border)">
        <div>
          <h1 class="text-4xl md:text-5xl font-bold text-(--rt-text) mb-4 leading-tight">Building Smart Adama</h1>
          <h2 class="text-2xl text-(--rt-muted) font-light">Comprehensive Guide</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-left max-w-2xl mx-auto bg-(--rt-surface-2) p-8 rounded-xl border border-(--rt-border)">
          <div>
            <h3 class="font-bold text-(--rt-text) mb-3 uppercase tracking-wider text-sm">Authors</h3>
            <ul class="space-y-1 text-(--rt-text-body)">
              <li v-for="author in authors" :key="author">{{ author }}</li>
            </ul>
          </div>
          <div>
            <h3 class="font-bold text-(--rt-text) mb-3 uppercase tracking-wider text-sm">Advisor and Reviewer</h3>
            <p class="text-(--rt-text-body)">Jima Gobena Dadi (MSc.)</p>
            
            <h3 class="font-bold text-(--rt-text) mt-6 mb-3 uppercase tracking-wider text-sm">Publication</h3>
            <p class="text-(--rt-text-body) text-sm leading-relaxed">
              Published by Harambe University<br>
              Adama, Ethiopia<br>
              First Edition: February 2025
            </p>
          </div>
        </div>
        
        <p class="text-xs text-(--rt-muted) max-w-2xl mx-auto leading-relaxed">
          Copyright © 2025. All rights reserved. No part of this book may be reproduced, stored in a retrieval system, or transmitted in any form or by any means, electronic, mechanical, photocopying, recording, or otherwise, without the prior written permission or consent of the publisher and Adama City Administration.
        </p>
      </section>

      <section class="max-w-3xl mx-auto">
        <h2 class="text-3xl font-bold text-(--rt-text) mb-6 text-center border-b border-(--rt-border) pb-4">Mayor’s Message</h2>
        <div class="prose prose-brand max-w-none text-[1.05em] leading-[1.85]">
          <p class="font-semibold text-(--rt-text)">Dear Citizens,</p>
          <p>I am excited to present our Smart Adama Guidebook, which demonstrates our commitment to building a smarter, sustainable, and inclusive urban environment. As we confront rapid population growth, environmental issues, public safety concerns, and rising service demands, it is clear that innovative solutions are needed. This guidebook details our strategic plan for implementing advanced technologies, promoting innovation, and leveraging data-driven initiatives to address these challenges.</p>
          <p>Through our smart Adama initiatives, we strive to improve residents’ quality of life by developing resilient public services, ensuring safety, and creating a competitive city that benefits both residents and local businesses.</p>
          <p>Our vision is centered around the aspiration to elevate the standard of living for every individual in our community. By uniting our efforts, we can collaboratively create an innovative hub that supports entrepreneurs, improves the overall business climate, and fosters a vibrant, inclusive, and livable city that is prepared to meet the demands and opportunities of the future.</p>
          <p>Furthermore, I would like to take this opportunity to extend my heartfelt gratitude to all our stakeholders. Your invaluable contributions and collaborative spirit have played a pivotal role in the ongoing transformation of Adama. It is through your dedication and hard work that we are witnessing significant advancements, and we wholeheartedly acknowledge the positive impact your efforts will undoubtedly have on shaping the future of our beloved city. Together, we are paving the way for a brighter tomorrow.</p>
          <div class="mt-8 text-right">
            <p class="font-bold text-xl text-(--rt-text) mb-1">Sincerely,</p>
            <p class="font-semibold text-(--rt-text)">Mr. Hailu Jelde</p>
            <p class="text-(--rt-muted) text-sm">Mayor of Adama City</p>
          </div>
        </div>
      </section>
    </div>

    <!-- ================= PAGE 2 ================= -->
    <div v-else-if="currentPage === 2" class="space-y-16 pb-8">
      <section class="max-w-3xl mx-auto">
        <h2 class="text-3xl font-bold text-(--rt-text) mb-8 text-center border-b border-(--rt-border) pb-4">Contents</h2>
        <div class="space-y-3">
          <div 
            v-for="item in toc" 
            :key="item.chapter" 
            @click="$emit('goToChapter', item.chapter)" 
            class="flex items-baseline gap-2 cursor-pointer group hover:opacity-80 transition-opacity"
          >
            <span class="font-bold text-(--rt-text) w-6 group-hover:text-(--rt-accent) transition-colors">{{ item.chapter }}</span>
            <span class="text-(--rt-text-body) font-medium group-hover:text-(--rt-accent) transition-colors">{{ item.title }}</span>
            <div class="flex-1 border-b-2 border-dotted border-(--rt-border) mx-2 relative top-[-4px]"></div>
            <span class="font-mono text-sm text-(--rt-muted) group-hover:text-(--rt-accent) transition-colors">{{ item.page }}</span>
          </div>
        </div>
      </section>

      <section class="max-w-3xl mx-auto">
        <h2 class="text-3xl font-bold text-(--rt-text) mb-8 text-center border-b border-(--rt-border) pb-4">List of Acronyms</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
          <div v-for="acronym in acronyms" :key="acronym.term" class="flex gap-4">
            <dt class="font-bold text-(--rt-text) w-16 shrink-0">{{ acronym.term }}</dt>
            <dd class="text-(--rt-text-body) text-sm">{{ acronym.desc }}</dd>
          </div>
        </div>
      </section>
    </div>

    <!-- ================= PAGE 3 ================= -->
    <div v-else-if="currentPage === 3" class="space-y-16 pb-8 pt-4">
      <section class="max-w-3xl mx-auto">
        <h2 class="text-3xl font-bold text-(--rt-text) mb-6 text-center border-b border-(--rt-border) pb-4">Preface</h2>
        <div class="prose prose-brand max-w-none text-[1.05em] leading-[1.85]">
          <p>As Adama City stands on the threshold of a transformation era in Ethiopian cities, this book serves as a comprehensive guide to envisioning and actualizing a smart city that is both innovative and inclusive. Achieving a smart city requires more than just incorporating innovative technologies; it also entails cultivating a well-balanced combination of sustainability, connectivity, and citizen involvement.</p>
          <p>The proactive participation of Adama’s residents in the smart city revolution is one of the main points addressed in this book. Residents’ involvement and engagement are essential to a smart city’s success because they add to the liveliness and sustainability of the city’s infrastructure. The chapters that follow will examine how community-driven initiatives and smart technologies may improve every aspect of urban living, from energy-efficient buildings and smart transportation to digital health care and e-governance.</p>
          <p>By working together, we can create a future in which Adama City not only successfully navigates the difficulties posed by urbanization, but also becomes a model for exclusivity, creativity, and sustainability. Allow this book to serve as your travel companion and road-map as we set out on this thrilling and revolutionary path to a smarter Adama City.</p>
        </div>
      </section>

      <section class="max-w-3xl mx-auto">
        <h2 class="text-3xl font-bold text-(--rt-text) mb-6 text-center border-b border-(--rt-border) pb-4">Acknowledgments</h2>
        <div class="prose prose-brand max-w-none text-[1.05em] leading-[1.85]">
          <p>We begin by expressing our deepest gratitude to the visionary leaders of Adama City, and in particular, to the current mayor, whose steadfast commitment to sustainability and innovation has been pivotal in bringing this ambitious project to fruition. The National Road Map of synergy has been a key driver of our initiatives, and we are truly appreciative of its role in shaping the direction of progress. We also extend our heartfelt thanks to the regional government officials, the public, and all key stakeholders for their valuable guidance and unwavering support throughout this journey.</p>
          <p>We are especially grateful to the head of Adama City’s Office of Science and Technology, as well as the skilled data analysts, IT specialists, and engineers from diverse disciplines. We would like to express our sincere gratitude to Mr. Jima Gobena for his invaluable contributions as both an advisor and a reviewer for this book.</p>
          <p>Moreover, we extend our heartfelt gratitude to the local businesses, community organizations, academic institutions, research centers, and citizens of Adama who participated in surveys, focus groups, and public consultations. Their enthusiastic engagement and constructive feedback have been crucial in ensuring our plans are practical and aligned with community needs.</p>
        </div>
      </section>
    </div>

  </div>
</template>