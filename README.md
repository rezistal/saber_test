# saber_test
Реализуйте функции сериализации и десериализации двусвязного списка, заданного следующим образом:

    class ListNode
    {
        public ListNode Prev;
        public ListNode Next;
        public ListNode Rand; //Произвольный элемент внутри списка
        public string Data;
    }
    
    class ListRand
    {
        public ListNode Head;
        public ListNode Tail;
        public int Count;
        
        public void Serialize(FileStream s)
        {
        }
        
        public void Deserialize(FileStream s)
        {
        }
    }


Примечание: сериализация подразумевает сохранение и восстановление полной структуры списка, включая взаимное соотношение его элементов между собой — в том числе ссылок на Rand элементы.
Алгоритмическая сложность решения должна быть меньше квадратичной.
Для выполнения задания можно использовать любой общеиспользуемый язык.
Тест нужно выполнить без использования библиотек/стандартных средств сериализации.
